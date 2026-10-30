<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\NotificationSyncer;
use Flarum\Tags\Tag;
use Flarum\User\Event\GroupsChanged;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Ramon\TagExperts\ExpertResolver;
use Ramon\TagExperts\Notification\BecameExpertBlueprint;
use Ramon\TagExperts\TagExpertEndorsement;
use Ramon\TagExperts\TagExpertSuggestion;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * Fila de revisão: aprovar coloca o membro no grupo de especialistas da
 * tag; dispensar arquiva a sugestão.
 *
 * @extends AbstractDatabaseResource<TagExpertSuggestion>
 */
class SuggestionResource extends AbstractDatabaseResource
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected ExpertResolver $resolver,
        protected NotificationSyncer $notifications,
        protected Dispatcher $events
    ) {
    }

    public function type(): string
    {
        return 'tag-expert-suggestions';
    }

    public function model(): string
    {
        return TagExpertSuggestion::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        if (! $context->getActor()->hasPermission('tagExperts.review')) {
            $query->whereRaw('1 = 0');

            return;
        }

        // A fila só mostra o que falta revisar; as resolvidas ficam no banco
        // para não voltarem como sugestão nova.
        if ($context instanceof Context && $context->listing(self::class)) {
            $query->where('status', TagExpertSuggestion::PENDING);
        }

        // Tag restrita que a moderação não enxerga também não entra na fila.
        $query->whereIn('tag_id', Tag::query()->whereVisibleTo($context->getActor())->select('tags.id'));
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->can('tagExperts.review')
                ->defaultSort('createdAt')
                ->paginate()
                ->eagerLoad(['user.groups', 'tag']),
            Endpoint\Update::make()
                ->authenticated()
                ->can('tagExperts.review')
                ->eagerLoad(['user', 'tag']),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('status')
                ->writable()
                ->in([TagExpertSuggestion::APPROVED, TagExpertSuggestion::DISMISSED]),
            Schema\Integer::make('endorsementCount')
                ->get(fn (TagExpertSuggestion $suggestion) => TagExpertEndorsement::query()
                    ->where(['tag_id' => $suggestion->tag_id, 'user_id' => $suggestion->user_id])
                    ->count()),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('resolvedAt'),
            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('tag')
                ->type('tags')
                ->includable(),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    public function updating(object $model, OriginalContext $context): ?object
    {
        /** @var TagExpertSuggestion $model */
        // Grupo escolhido na aprovação, quando a tag tem mais de um; vai no meta.
        $groupId = $context->body()['meta']['groupId'] ?? null;
        $alreadyResolved = fn () => new ValidationException(['status' => $this->translator->trans('ramon-tag-experts.api.already_resolved')]);

        if ($model->getOriginal('status') !== TagExpertSuggestion::PENDING) {
            throw $alreadyResolved();
        }

        $model->resolved_at = Carbon::now();
        $model->resolved_by_id = (int) $context->getActor()->id;

        if ($model->status === TagExpertSuggestion::APPROVED) {
            // Só os grupos que valem (sem Admins, Mods nem ocultos).
            $groups = $this->resolver->expertGroupsByTag([(int) $model->tag_id])[(int) $model->tag_id] ?? [];
            $group = $groupId !== null ? filter_var($groupId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : ($groups[0] ?? null);

            if (! is_int($group) || ! in_array($group, $groups, true)) {
                throw new ValidationException(['groupId' => $this->translator->trans('ramon-tag-experts.api.no_expert_group')]);
            }

            $model->afterSave(function (TagExpertSuggestion $suggestion) use ($group, $context) {
                $user = $suggestion->user;
                $old = $user->groups()->get()->all();
                $user->groups()->syncWithoutDetaching([$group]);
                // Mesmo evento da edição de grupos no admin: recalcula as
                // respostas de especialista dessa pessoa.
                $this->events->dispatch(new GroupsChanged($user, $old, $context->getActor()));
                $this->notifications->sync(new BecameExpertBlueprint($suggestion->tag), [$user]);
            });
        }

        // Duas revisões ao mesmo tempo: só a que tirar a sugestão de "pendente"
        // segue (senão a pessoa entraria no grupo e seria avisada duas vezes).
        $claimed = TagExpertSuggestion::query()
            ->whereKey($model->id)
            ->where('status', TagExpertSuggestion::PENDING)
            ->update(['status' => $model->status, 'resolved_at' => $model->resolved_at, 'resolved_by_id' => $model->resolved_by_id]);

        if ($claimed === 0) {
            throw $alreadyResolved();
        }

        return parent::updating($model, $context);
    }
}

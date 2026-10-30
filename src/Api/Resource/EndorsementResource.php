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
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Ramon\TagExperts\ExpertResolver;
use Ramon\TagExperts\TagExpertEndorsement;
use Ramon\TagExperts\TagExpertSuggestion;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * Endossos que o membro logado deu. Ninguém vê os endossos dos outros: o
 * que fica público é só o resultado, depois da revisão.
 *
 * @extends AbstractDatabaseResource<TagExpertEndorsement>
 */
class EndorsementResource extends AbstractDatabaseResource
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected TranslatorInterface $translator,
        protected ExpertResolver $resolver
    ) {
    }

    public function type(): string
    {
        return 'tag-expert-endorsements';
    }

    public function model(): string
    {
        return TagExpertEndorsement::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $query->where('endorser_id', (int) $context->getActor()->id);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated(),
            Endpoint\Create::make()
                ->authenticated()
                ->can('tagExperts.endorse'),
            Endpoint\Delete::make()
                ->authenticated(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\DateTime::make('createdAt'),
            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->requiredOnCreate()
                ->writableOnCreate(),
            Schema\Relationship\ToOne::make('tag')
                ->type('tags')
                ->requiredOnCreate()
                ->writableOnCreate(),
        ];
    }

    public function newModel(OriginalContext $context): object
    {
        $endorsement = new TagExpertEndorsement();
        $endorsement->endorser_id = (int) $context->getActor()->id;
        $endorsement->created_at = Carbon::now();

        return $endorsement;
    }

    public function creating(object $model, OriginalContext $context): ?object
    {
        /** @var TagExpertEndorsement $model */
        $fail = fn (string $key, array $params = []) => throw new ValidationException([
            'endorsement' => $this->translator->trans("ramon-tag-experts.api.$key", $params),
        ]);

        /** @var Tag $tag */
        $tag = $model->tag;
        /** @var User $user */
        $user = $model->user;

        if (! $tag->accepts_expert_endorsements) {
            $fail('not_accepting');
        }
        if ($user->id === $model->endorser_id) {
            $fail('self');
        }
        if ($this->isExpert($user, (int) $tag->id)) {
            $fail('already_expert');
        }
        if (TagExpertEndorsement::query()->where(['tag_id' => $tag->id, 'user_id' => $user->id, 'endorser_id' => $model->endorser_id])->exists()) {
            $fail('already_endorsed');
        }

        $perDay = max(1, (int) $this->settings->get('ramon-tag-experts.endorsements_per_day', 3));
        $today = TagExpertEndorsement::query()
            ->where('endorser_id', $model->endorser_id)
            ->where('created_at', '>', Carbon::now()->subDay())
            ->count();

        if ($today >= $perDay) {
            $fail('limit_reached', ['{max}' => $perDay]);
        }

        return parent::creating($model, $context);
    }

    public function created(object $model, OriginalContext $context): ?object
    {
        /** @var TagExpertEndorsement $model */
        $threshold = max(1, (int) $this->settings->get('ramon-tag-experts.endorsement_threshold', 3));
        $count = TagExpertEndorsement::query()->where(['tag_id' => $model->tag_id, 'user_id' => $model->user_id])->count();

        // Uma sugestão por pessoa e tag: dispensada não volta sozinha.
        if ($count >= $threshold && ! TagExpertSuggestion::query()->where(['tag_id' => $model->tag_id, 'user_id' => $model->user_id])->exists()) {
            $suggestion = new TagExpertSuggestion();
            $suggestion->tag_id = $model->tag_id;
            $suggestion->user_id = $model->user_id;
            $suggestion->status = TagExpertSuggestion::PENDING;
            $suggestion->created_at = Carbon::now();
            $suggestion->save();
        }

        return parent::created($model, $context);
    }

    protected function isExpert(User $user, int $tagId): bool
    {
        $groups = $this->resolver->expertGroupsByTag([$tagId])[$tagId] ?? [];

        return $groups !== [] && $user->groups()->whereIn('groups.id', $groups)->exists();
    }
}

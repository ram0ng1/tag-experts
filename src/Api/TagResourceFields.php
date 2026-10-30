<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Group\Group;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Ramon\TagExperts\ExpertGroups;
use Ramon\TagExperts\ExpertResolver;
use Ramon\TagExperts\Job\RecalculateExpertPosts;

/**
 * Grupos de especialistas e endossos, na edição da tag. Públicos: o fórum
 * usa os grupos para marcar as respostas de especialista sem perguntar ao
 * servidor post a post.
 */
class TagResourceFields
{
    public const MAX_GROUPS = 50;

    public function __construct(
        protected ExpertGroups $groups,
        protected ConnectionInterface $db,
        protected ExpertResolver $resolver,
        protected Dispatcher $bus
    ) {
    }

    /**
     * @return array<Schema\Attribute>
     */
    public function __invoke(): array
    {
        $canEdit = fn (Tag $tag, Context $context) => $context->getActor()->can('edit', $tag);

        return [
            Schema\Arr::make('expertGroupIds')
                ->get(fn (Tag $tag, Context $context) => $this->groups->forTag($context->request, (int) $tag->id))
                ->writable($canEdit)
                ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail) {
                    $valid = is_array($value) && array_is_list($value) && count($value) <= self::MAX_GROUPS;

                    if (! $valid || array_filter($value, fn ($id) => filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false)) {
                        $fail('Invalid groups.');
                    }
                })
                ->set(function (Tag $tag, array $value) {
                    // Só grupos que podem ser de especialistas (ExpertGroups::INELIGIBLE,
                    // e nada de grupo oculto); os outros são ignorados.
                    $ids = Group::query()
                        ->whereIn('id', array_map('intval', $value))
                        ->whereNotIn('id', ExpertGroups::INELIGIBLE)
                        ->where('is_hidden', false)
                        ->pluck('id')
                        ->all();

                    $tag->afterSave(function (Tag $tag) use ($ids) {
                        $this->db->table('tag_expert_groups')->where('tag_id', $tag->id)->delete();
                        $this->db->table('tag_expert_groups')->insert(array_map(fn ($id) => ['tag_id' => $tag->id, 'group_id' => $id], $ids));

                        $discussions = $this->resolver->discussionsInTags([(int) $tag->id]);
                        if ($discussions) {
                            $this->bus->dispatch(new RecalculateExpertPosts($discussions));
                        }
                    });
                }),
            Schema\Boolean::make('acceptsExpertEndorsements')
                ->property('accepts_expert_endorsements')
                ->get(fn (Tag $tag) => (bool) $tag->accepts_expert_endorsements)
                ->writable($canEdit),
        ];
    }
}

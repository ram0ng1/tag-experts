<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Listener;

use Flarum\Database\AbstractModel;
use Flarum\Group\Event\Deleting as GroupDeleting;
use Flarum\Settings\Event\Saved as SettingsSaved;
use Flarum\Tags\Event\Deleting as TagDeleting;
use Flarum\User\Event\Deleting as UserDeleting;
use Illuminate\Contracts\Bus\Dispatcher;
use Ramon\TagExperts\ExpertResolver;
use Ramon\TagExperts\Job\RecalculateExpertPosts;

/**
 * O que tira um especialista de cena sem passar pelos posts: tag, grupo ou
 * pessoa excluídos, e a opção do primeiro post. As discussões afetadas são
 * lidas antes da exclusão (depois as ligações já sumiram) e recalculadas
 * depois dela.
 */
class RecalculateOnRemoval
{
    public function __construct(
        protected ExpertResolver $resolver,
        protected Dispatcher $bus
    ) {
    }

    public function handle(object $event): void
    {
        match (true) {
            $event instanceof TagDeleting => $this->afterDelete($event->tag, $this->resolver->discussionsInTags([(int) $event->tag->id])),
            $event instanceof GroupDeleting => $this->afterDelete($event->group, $this->resolver->discussionsInTags(
                $this->resolver->expertGroupRows()->where('tag_expert_groups.group_id', $event->group->id)->pluck('tag_expert_groups.tag_id')->map(fn ($id) => (int) $id)->all()
            )),
            $event instanceof UserDeleting => $this->afterDelete($event->user, $this->resolver->discussionsAnsweredBy((int) $event->user->id)),
            $event instanceof SettingsSaved => array_key_exists('ramon-tag-experts.first_post_counts', $event->settings)
                ? $this->dispatch($this->resolver->discussionsWithExperts())
                : null,
            default => null,
        };
    }

    /**
     * @param list<int> $discussions
     */
    protected function afterDelete(AbstractModel $model, array $discussions): void
    {
        if ($discussions) {
            $model->afterDelete(fn () => $this->dispatch($discussions));
        }
    }

    /**
     * @param list<int> $discussions
     */
    protected function dispatch(array $discussions): void
    {
        if ($discussions) {
            $this->bus->dispatch(new RecalculateExpertPosts($discussions));
        }
    }
}

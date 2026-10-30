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

use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Tags\Event\DiscussionWasTagged;
use Ramon\TagExperts\ExpertResolver;

/**
 * Mantém a primeira resposta de especialista de uma discussão quando um
 * post entra, some ou volta, ou quando as tags da discussão mudam.
 */
class UpdateExpertPost
{
    public function __construct(
        protected ExpertResolver $resolver
    ) {
    }

    /**
     * @param Posted|Hidden|Restored|Deleted|DiscussionWasTagged|\Flarum\Approval\Event\PostWasApproved $event
     */
    public function handle(object $event): void
    {
        $discussionId = $event instanceof DiscussionWasTagged
            ? (int) $event->discussion->id
            : (int) $event->post->discussion_id;

        // Uma resposta depois da primeira de especialista não muda nada.
        if ($event instanceof Posted && $event->post->discussion?->expert_post_id !== null) {
            return;
        }

        $this->resolver->recalculate([$discussionId]);
    }
}

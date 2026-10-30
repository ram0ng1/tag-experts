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

use Flarum\User\Event\GroupsChanged;
use Illuminate\Contracts\Bus\Dispatcher;
use Ramon\TagExperts\ExpertResolver;
use Ramon\TagExperts\Job\RecalculateExpertPosts;

/**
 * Entrar ou sair de um grupo de especialistas muda as respostas dessa
 * pessoa nas tags em que ela é (ou deixou de ser) especialista.
 */
class RecalculateOnGroupsChanged
{
    public function __construct(
        protected ExpertResolver $resolver,
        protected Dispatcher $bus
    ) {
    }

    public function handle(GroupsChanged $event): void
    {
        $discussions = $this->resolver->discussionsOf((int) $event->user->id);

        if ($discussions) {
            $this->bus->dispatch(new RecalculateExpertPosts($discussions));
        }
    }
}

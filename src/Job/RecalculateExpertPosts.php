<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Job;

use Flarum\Queue\AbstractJob;
use Ramon\TagExperts\ExpertResolver;

/**
 * Recalcula a resposta de especialista de várias discussões fora da
 * requisição: trocar os grupos de uma tag grande mexe em milhares.
 */
class RecalculateExpertPosts extends AbstractJob
{
    /**
     * @param list<int> $discussionIds
     */
    public function __construct(
        public array $discussionIds
    ) {
    }

    public function handle(ExpertResolver $resolver): void
    {
        $resolver->recalculate($this->discussionIds);
    }
}

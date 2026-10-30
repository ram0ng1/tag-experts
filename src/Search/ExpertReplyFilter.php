<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * `has:expert-reply` (filtro `expertReply`): discussões com resposta de
 * especialista. A coluna é mantida pelos listeners, então é só um IS NULL.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class ExpertReplyFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'expertReply';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $negate
            ? $state->getQuery()->whereNull('discussions.expert_post_id')
            : $state->getQuery()->whereNotNull('discussions.expert_post_id');
    }
}

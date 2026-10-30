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

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Ramon\TagExperts\TagExpertEndorsement;

class EndorsementSearcher extends AbstractSearcher
{
    /** @return Builder<TagExpertEndorsement> */
    public function getQuery(User $actor): Builder
    {
        return TagExpertEndorsement::query()->where('endorser_id', (int) $actor->id);
    }
}

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

class ForumResourceFields
{
    /**
     * @return array<Schema\Attribute>
     */
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canEndorseTagExperts')
                ->get(fn (object $forum, Context $context) => $context->getActor()->hasPermission('tagExperts.endorse')),
            Schema\Boolean::make('canReviewTagExperts')
                ->get(fn (object $forum, Context $context) => $context->getActor()->hasPermission('tagExperts.review')),
        ];
    }
}

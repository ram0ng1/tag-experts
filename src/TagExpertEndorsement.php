<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um membro dizendo que outro entende de uma tag.
 *
 * @property int $id
 * @property int $tag_id
 * @property int $user_id
 * @property int $endorser_id
 * @property Carbon $created_at
 * @property-read Tag|null $tag
 * @property-read User|null $user
 * @property-read User|null $endorser
 */
class TagExpertEndorsement extends AbstractModel
{
    protected $table = 'tag_expert_endorsements';

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'tag_id' => 'integer',
        'user_id' => 'integer',
        'endorser_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Tag, $this> */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorser_id');
    }
}

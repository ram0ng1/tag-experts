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
 * Membro que juntou endossos suficientes numa tag e espera a revisão.
 *
 * @property int $id
 * @property int $tag_id
 * @property int $user_id
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon|null $resolved_at
 * @property int|null $resolved_by_id
 * @property-read Tag|null $tag
 * @property-read User|null $user
 * @property-read User|null $resolvedBy
 */
class TagExpertSuggestion extends AbstractModel
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const DISMISSED = 'dismissed';

    protected $table = 'tag_expert_suggestions';

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'tag_id' => 'integer',
        'user_id' => 'integer',
        'resolved_by_id' => 'integer',
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
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
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}

<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Tags\Tag;
use Flarum\User\User;

/**
 * Avisa o membro de que a revisão o reconheceu como especialista numa tag.
 */
class BecameExpertBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(
        public Tag $tag
    ) {
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->tag;
    }

    public function getFromUser(): ?User
    {
        return null;
    }

    public function getData(): mixed
    {
        return null;
    }

    public static function getType(): string
    {
        return 'tagExpertApproved';
    }

    public static function getSubjectModel(): string
    {
        return Tag::class;
    }
}

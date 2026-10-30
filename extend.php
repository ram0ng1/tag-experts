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

use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Resource\ForumResource;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Gdpr\Extend\UserData;
use Flarum\Group\Event\Deleting as GroupDeleting;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Settings\Event\Saved as SettingsSaved;
use Flarum\Tags\Api\Resource\TagResource;
use Flarum\Tags\Event\Deleting as TagDeleting;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Flarum\User\Event\Deleting as UserDeleting;
use Flarum\User\Event\GroupsChanged;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        // Fila de sugestões da moderação; o acesso é conferido pela API.
        ->route('/tag-experts/suggestions', 'tagExperts.suggestions'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings())
        ->default('ramon-tag-experts.endorsement_threshold', 3)
        ->default('ramon-tag-experts.endorsements_per_day', 3)
        ->default('ramon-tag-experts.first_post_counts', false)
        // label: ícone e texto; icon: só o ícone, com o texto na dica.
        ->default('ramon-tag-experts.badge_style', 'label')
        ->serializeToForum('tagExpertsBadgeStyle', 'ramon-tag-experts.badge_style'),

    (new Extend\Model(Tag::class))
        ->cast('accepts_expert_endorsements', 'boolean'),

    (new Extend\Model(Discussion::class))
        ->cast('expert_post_id', 'integer')
        ->cast('expert_post_number', 'integer'),

    new Extend\ApiResource(Api\Resource\EndorsementResource::class),
    new Extend\ApiResource(Api\Resource\SuggestionResource::class),

    (new Extend\ApiResource(TagResource::class))
        ->fields(Api\TagResourceFields::class),

    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(Api\DiscussionResourceFields::class),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(Api\ForumResourceFields::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addSearcher(TagExpertEndorsement::class, Search\EndorsementSearcher::class)
        ->addFilter(Search\EndorsementSearcher::class, Search\EndorsedUserFilter::class)
        ->addFilter(DiscussionSearcher::class, Search\ExpertReplyFilter::class),

    (new Extend\Notification())
        ->type(Notification\BecameExpertBlueprint::class, ['alert']),

    (new Extend\Event())
        ->listen(Posted::class, Listener\UpdateExpertPost::class)
        ->listen(Hidden::class, Listener\UpdateExpertPost::class)
        ->listen(Restored::class, Listener\UpdateExpertPost::class)
        ->listen(Deleted::class, Listener\UpdateExpertPost::class)
        ->listen(DiscussionWasTagged::class, Listener\UpdateExpertPost::class)
        ->listen(GroupsChanged::class, Listener\RecalculateOnGroupsChanged::class)
        ->listen(TagDeleting::class, Listener\RecalculateOnRemoval::class)
        ->listen(GroupDeleting::class, Listener\RecalculateOnRemoval::class)
        ->listen(UserDeleting::class, Listener\RecalculateOnRemoval::class)
        ->listen(SettingsSaved::class, Listener\RecalculateOnRemoval::class),

    (new Extend\Console())
        ->command(Console\RecalculateCommand::class),

    (new Extend\Conditional())
        // Post aprovado na moderação vira visível só agora.
        ->whenExtensionEnabled('flarum-approval', fn () => [
            (new Extend\Event())
                ->listen(\Flarum\Approval\Event\PostWasApproved::class, Listener\UpdateExpertPost::class),
        ])
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new UserData())
                ->addType(Data\Endorsements::class),
        ]),
];

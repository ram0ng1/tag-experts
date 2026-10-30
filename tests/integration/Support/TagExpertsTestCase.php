<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Tests\integration\Support;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Base dos testes. Usuários: 1 admin, 2 `normal`, 3 `especialista`
 * (grupo 5, de especialistas da tag 1), 4 `moderadora`, 5 a 7 recomendam.
 * Tags: 1 aceita recomendações e tem o grupo 5; 2 não tem especialistas.
 */
abstract class TagExpertsTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    public const EXPERT_GROUP = 5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'ramon-tag-experts');

        $users = [$this->normalUser()];
        foreach ([3 => 'especialista', 4 => 'moderadora', 5 => 'endossa5', 6 => 'endossa6', 7 => 'endossa7'] as $id => $name) {
            $users[] = ['id' => $id, 'username' => $name, 'email' => "$name@machine.local", 'password' => 'too-obscure', 'is_email_confirmed' => 1];
        }

        $this->prepareDatabase([
            User::class => $users,
            'groups' => [['id' => self::EXPERT_GROUP, 'name_singular' => 'Especialista', 'name_plural' => 'Especialistas', 'color' => null, 'icon' => null, 'is_hidden' => 0]],
            'group_user' => [
                ['user_id' => 3, 'group_id' => self::EXPERT_GROUP],
                ['user_id' => 4, 'group_id' => Group::MODERATOR_ID],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'PHP', 'slug' => 'php', 'position' => 0, 'accepts_expert_endorsements' => 1],
                ['id' => 2, 'name' => 'Café', 'slug' => 'cafe', 'position' => 1, 'accepts_expert_endorsements' => 0],
            ],
            'tag_expert_groups' => [['tag_id' => 1, 'group_id' => self::EXPERT_GROUP]],
            'discussions' => [
                ['id' => 1, 'title' => 'Dúvida de PHP', 'slug' => 'php', 'created_at' => Carbon::now()->subDay(), 'last_posted_at' => Carbon::now()->subDay(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'is_private' => 0],
                ['id' => 2, 'title' => 'Melhor café', 'slug' => 'cafe', 'created_at' => Carbon::now()->subDay(), 'last_posted_at' => Carbon::now()->subDay(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1, 'last_post_number' => 1, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now()->subDay(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Como faço?</p></t>', 'is_private' => 0],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'created_at' => Carbon::now()->subDay(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Qual?</p></t>', 'is_private' => 0],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
            ],
        ]);
    }

    protected function reply(int $userId, int $discussionId, string $text = 'Resposta'): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/posts', [
            'authenticatedAs' => $userId,
            'json' => ['data' => ['type' => 'posts', 'attributes' => ['content' => $text], 'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => (string) $discussionId]]]]],
        ]));
    }

    protected function endorse(int $endorserId, int $userId, int $tagId): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/tag-expert-endorsements', [
            'authenticatedAs' => $endorserId,
            'json' => ['data' => ['type' => 'tag-expert-endorsements', 'relationships' => [
                'user' => ['data' => ['type' => 'users', 'id' => (string) $userId]],
                'tag' => ['data' => ['type' => 'tags', 'id' => (string) $tagId]],
            ]]],
        ]));
    }

    protected function expertPostNumber(int $discussionId): ?int
    {
        $value = $this->database()->table('discussions')->where('id', $discussionId)->value('expert_post_number');

        return $value === null ? null : (int) $value;
    }

    /** @return array<string, mixed> */
    protected function json(ResponseInterface $response): array
    {
        return (array) json_decode((string) $response->getBody(), true);
    }
}

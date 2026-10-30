<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Tests\integration\api;

use Carbon\Carbon;
use Ramon\TagExperts\Tests\integration\Support\TagExpertsTestCase;

/**
 * Revisão das sugestões: só quem tem `tagExperts.review`.
 */
class SuggestionTest extends TagExpertsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase(['tag_expert_suggestions' => [
            ['id' => 1, 'tag_id' => 1, 'user_id' => 5, 'status' => 'pending', 'created_at' => Carbon::now()],
            ['id' => 2, 'tag_id' => 1, 'user_id' => 6, 'status' => 'dismissed', 'created_at' => Carbon::now()->subDay()],
        ]]);
        // As três recomendações que levaram à sugestão 1 (o limiar padrão).
        $this->prepareDatabase(['tag_expert_endorsements' => array_map(
            fn (int $endorser) => ['tag_id' => 1, 'user_id' => 5, 'endorser_id' => $endorser, 'created_at' => Carbon::now()],
            [2, 6, 7]
        )]);
    }

    private function resolve(int $actor, string $status, ?int $groupId = null): \Psr\Http\Message\ResponseInterface
    {
        $body = ['data' => ['type' => 'tag-expert-suggestions', 'id' => '1', 'attributes' => ['status' => $status]]];
        if ($groupId !== null) {
            $body['meta'] = ['groupId' => $groupId];
        }

        return $this->send($this->request('PATCH', '/api/tag-expert-suggestions/1', ['authenticatedAs' => $actor, 'json' => $body]));
    }

    public function test_membro_nao_ve_a_fila(): void
    {
        $this->assertEquals(403, $this->send($this->request('GET', '/api/tag-expert-suggestions', ['authenticatedAs' => 2]))->getStatusCode());
        $this->assertContains($this->resolve(2, 'approved')->getStatusCode(), [403, 404]);
    }

    public function test_moderacao_lista(): void
    {
        $list = $this->json($this->send($this->request('GET', '/api/tag-expert-suggestions', ['authenticatedAs' => 4])->withQueryParams(['include' => 'user,tag'])));

        // A dispensada não volta para a fila.
        $this->assertSame(['1'], array_column($list['data'], 'id'));
    }

    public function test_aprovar_poe_no_grupo_e_avisa(): void
    {
        $response = $this->resolve(4, 'approved');

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue($this->database()->table('group_user')->where(['user_id' => 5, 'group_id' => self::EXPERT_GROUP])->exists());
        $this->assertTrue($this->database()->table('notifications')->where(['user_id' => 5, 'type' => 'tagExpertApproved'])->exists());
        $this->assertSame('approved', $this->database()->table('tag_expert_suggestions')->where('id', 1)->value('status'));
    }

    public function test_aprovar_recalcula_as_respostas(): void
    {
        $this->reply(5, 1);
        $this->assertNull($this->expertPostNumber(1));

        $this->resolve(4, 'approved');

        $this->assertSame(2, $this->expertPostNumber(1));
    }

    public function test_dispensar(): void
    {
        $this->assertEquals(200, $this->resolve(4, 'dismissed')->getStatusCode());
        $this->assertFalse($this->database()->table('group_user')->where(['user_id' => 5, 'group_id' => self::EXPERT_GROUP])->exists());
    }

    public function test_nao_resolve_duas_vezes(): void
    {
        $this->resolve(4, 'dismissed');

        $this->assertEquals(422, $this->resolve(4, 'approved')->getStatusCode());
    }

    public function test_grupo_escolhido_tem_de_ser_da_tag(): void
    {
        $this->assertEquals(422, $this->resolve(4, 'approved', 4)->getStatusCode());
        $this->assertFalse($this->database()->table('group_user')->where(['user_id' => 5, 'group_id' => 4])->exists());
    }
}

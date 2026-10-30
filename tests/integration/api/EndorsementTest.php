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
use PHPUnit\Framework\Attributes\DataProvider;
use Ramon\TagExperts\Tests\integration\Support\TagExpertsTestCase;

/**
 * Recomendações: quem pode, quantas vezes e quando viram sugestão.
 */
class EndorsementTest extends TagExpertsTestCase
{
    public function test_usuario_recomenda_e_ve_so_as_proprias(): void
    {
        $response = $this->endorse(2, 5, 1);
        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
        $this->endorse(6, 5, 1);

        $mine = $this->json($this->send($this->request('GET', '/api/tag-expert-endorsements', ['authenticatedAs' => 2])->withQueryParams(['filter' => ['user' => '5']])));

        $this->assertCount(1, $mine['data']);
    }

    /** @return array<string, array{int, int, int}> */
    public static function refused(): array
    {
        return [
            'a si mesmo'          => [2, 2, 1],
            'tag sem recomendações'    => [2, 5, 2],
            'já é especialista'   => [2, 3, 1],
        ];
    }

    #[DataProvider('refused')]
    public function test_recusa(int $endorser, int $user, int $tag): void
    {
        $this->assertEquals(422, $this->endorse($endorser, $user, $tag)->getStatusCode());
    }

    public function test_recusa_recomendacao_repetida(): void
    {
        $this->endorse(2, 5, 1);

        $this->assertEquals(422, $this->endorse(2, 5, 1)->getStatusCode());
    }

    public function test_limite_diario(): void
    {
        $this->setting('ramon-tag-experts.endorsements_per_day', 1);
        $this->endorse(2, 5, 1);

        $this->assertEquals(422, $this->endorse(2, 6, 1)->getStatusCode());

        Carbon::setTestNow(Carbon::now()->addDays(2));
        $this->assertEquals(201, $this->endorse(2, 6, 1)->getStatusCode());
        Carbon::setTestNow();
    }

    public function test_sem_permissao(): void
    {
        $this->database()->table('group_permission')->where('permission', 'tagExperts.endorse')->delete();

        $this->assertEquals(403, $this->endorse(2, 5, 1)->getStatusCode());
    }

    public function test_visitante_nao_recomenda_nem_lista(): void
    {
        $this->assertEquals(401, $this->send($this->request('GET', '/api/tag-expert-endorsements'))->getStatusCode());
    }

    public function test_desfaz_o_proprio_e_nao_o_dos_outros(): void
    {
        $id = $this->json($this->endorse(2, 5, 1))['data']['id'];

        $this->assertEquals(404, $this->send($this->request('DELETE', "/api/tag-expert-endorsements/$id", ['authenticatedAs' => 6]))->getStatusCode());
        $this->assertEquals(204, $this->send($this->request('DELETE', "/api/tag-expert-endorsements/$id", ['authenticatedAs' => 2]))->getStatusCode());
    }

    public function test_limiar_cria_sugestao(): void
    {
        $this->endorse(2, 5, 1);
        $this->endorse(6, 5, 1);
        $this->assertSame(0, $this->database()->table('tag_expert_suggestions')->count());

        $this->endorse(7, 5, 1);

        $this->assertSame(1, $this->database()->table('tag_expert_suggestions')->where(['user_id' => 5, 'tag_id' => 1, 'status' => 'pending'])->count());
    }

    public function test_sugestao_dispensada_nao_volta(): void
    {
        $this->setting('ramon-tag-experts.endorsement_threshold', 1);
        $this->prepareDatabase(['tag_expert_suggestions' => [['id' => 1, 'tag_id' => 1, 'user_id' => 5, 'status' => 'dismissed', 'created_at' => Carbon::now()]]]);

        $this->endorse(2, 5, 1);

        $this->assertSame(['dismissed'], $this->database()->table('tag_expert_suggestions')->pluck('status')->all());
    }
}

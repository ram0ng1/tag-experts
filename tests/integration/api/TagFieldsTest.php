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

use Ramon\TagExperts\Tests\integration\Support\TagExpertsTestCase;

class TagFieldsTest extends TagExpertsTestCase
{
    public function test_visitante_ve_os_grupos_e_os_endossos(): void
    {
        $tags = $this->json($this->send($this->request('GET', '/api/tags')))['data'];
        $php = collect($tags)->firstWhere('id', '1')['attributes'];

        $this->assertSame([self::EXPERT_GROUP], $php['expertGroupIds']);
        $this->assertTrue($php['acceptsExpertEndorsements']);
    }

    public function test_membro_nao_muda_os_especialistas(): void
    {
        $response = $this->send($this->request('PATCH', '/api/tags/1', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'tags', 'id' => '1', 'attributes' => ['expertGroupIds' => [3], 'acceptsExpertEndorsements' => false]]],
        ]));

        $this->assertContains($response->getStatusCode(), [403, 422]);
        $this->assertSame([self::EXPERT_GROUP], $this->database()->table('tag_expert_groups')->where('tag_id', 1)->pluck('group_id')->map(fn ($id) => (int) $id)->all());
    }

    /** Visitantes e Membros (todo mundo) não fazem sentido como especialistas. */
    public function test_visitantes_e_membros_nao_podem_ser_grupo_de_especialistas(): void
    {
        $this->send($this->request('PATCH', '/api/tags/2', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'tags', 'id' => '2', 'attributes' => ['expertGroupIds' => [2, 3, self::EXPERT_GROUP], 'acceptsExpertEndorsements' => true]]],
        ]));

        $this->assertSame([self::EXPERT_GROUP], $this->database()->table('tag_expert_groups')->where('tag_id', 2)->pluck('group_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(1, (int) $this->database()->table('tags')->where('id', 2)->value('accepts_expert_endorsements'));
    }
}

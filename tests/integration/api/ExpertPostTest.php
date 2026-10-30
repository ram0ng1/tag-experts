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

/**
 * A primeira resposta de especialista de cada discussão.
 */
class ExpertPostTest extends TagExpertsTestCase
{
    public function test_resposta_de_especialista_marca_a_discussao(): void
    {
        $response = $this->reply(3, 1);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(2, $this->expertPostNumber(1));

        $discussion = $this->json($this->send($this->request('GET', '/api/discussions/1')));
        $this->assertSame(2, $discussion['data']['attributes']['expertPostNumber']);
    }

    public function test_resposta_comum_nao_marca(): void
    {
        $this->reply(2, 1);

        $this->assertNull($this->expertPostNumber(1));
    }

    public function test_especialista_de_outra_tag_nao_marca(): void
    {
        $this->reply(3, 2);

        $this->assertNull($this->expertPostNumber(2));
    }

    public function test_primeiro_post_so_conta_com_a_opcao(): void
    {
        $this->database()->table('posts')->where('id', 1)->update(['user_id' => 3]);
        $this->app()->getContainer()->make(\Ramon\TagExperts\ExpertResolver::class)->recalculate([1]);
        $this->assertNull($this->expertPostNumber(1));

        $this->app()->getContainer()->make(\Flarum\Settings\SettingsRepositoryInterface::class)->set('ramon-tag-experts.first_post_counts', '1');
        $this->app()->getContainer()->make(\Ramon\TagExperts\ExpertResolver::class)->recalculate([1]);
        $this->assertSame(1, $this->expertPostNumber(1));
    }

    public function test_ocultar_a_resposta_desmarca(): void
    {
        $id = $this->json($this->reply(3, 1))['data']['id'];

        $hide = $this->send($this->request('PATCH', "/api/posts/$id", [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'posts', 'id' => $id, 'attributes' => ['isHidden' => true]]],
        ]));

        $this->assertEquals(200, $hide->getStatusCode(), (string) $hide->getBody());
        $this->assertNull($this->expertPostNumber(1));
    }

    public function test_trocar_os_grupos_da_tag_recalcula(): void
    {
        $this->prepareDatabase([
            'groups' => [['id' => 6, 'name_singular' => 'Professor', 'name_plural' => 'Professores', 'color' => null, 'icon' => null, 'is_hidden' => 0]],
            'group_user' => [['user_id' => 4, 'group_id' => 6]],
        ]);
        $this->reply(4, 1);

        $response = $this->send($this->request('PATCH', '/api/tags/1', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'tags', 'id' => '1', 'attributes' => ['expertGroupIds' => [6]]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame([6], $this->json($response)['data']['attributes']['expertGroupIds']);
        // A moderadora agora é especialista em PHP: a resposta dela conta.
        $this->assertSame(2, $this->expertPostNumber(1));
    }

    public function test_mudar_a_tag_da_discussao_recalcula(): void
    {
        $this->reply(3, 2);
        $this->assertNull($this->expertPostNumber(2));

        $response = $this->send($this->request('PATCH', '/api/discussions/2', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'discussions', 'id' => '2', 'relationships' => ['tags' => ['data' => [['type' => 'tags', 'id' => '1']]]]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(2, $this->expertPostNumber(2));
    }

    public function test_filtro_resposta_de_especialista(): void
    {
        $this->reply(3, 1);

        $with = $this->json($this->send($this->request('GET', '/api/discussions')->withQueryParams(['filter' => ['expertReply' => 'true']])));
        $without = $this->json($this->send($this->request('GET', '/api/discussions')->withQueryParams(['filter' => ['-expertReply' => 'true']])));

        $this->assertSame(['1'], array_column($with['data'], 'id'));
        $this->assertSame(['2'], array_column($without['data'], 'id'));
    }

    public function test_comando_recalcula_tudo(): void
    {
        $this->reply(3, 1);
        $this->database()->table('discussions')->update(['expert_post_id' => null, 'expert_post_number' => null]);

        $command = $this->app()->getContainer()->make(\Ramon\TagExperts\Console\RecalculateCommand::class);
        (new \Symfony\Component\Console\Tester\CommandTester($command))->execute([]);

        $this->assertSame(2, $this->expertPostNumber(1));
    }
}

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
namespace Ramon\TagExperts\Tests\integration\api;

use Carbon\Carbon;
use Flarum\User\User;
use Ramon\TagExperts\Data\Endorsements;
use Ramon\TagExperts\Tests\integration\Support\TagExpertsTestCase;

/**
 * Os achados da auditoria de segurança: cada teste falha sem a correção.
 */
class HardeningTest extends TagExpertsTestCase
{
    public const HIDDEN_GROUP = 6;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'groups' => [['id' => self::HIDDEN_GROUP, 'name_singular' => 'Secreto', 'name_plural' => 'Secretos', 'color' => null, 'icon' => null, 'is_hidden' => 1]],
            'group_user' => [['user_id' => 7, 'group_id' => self::HIDDEN_GROUP]],
        ]);
    }

    private function groupsOfTag(int $tagId): array
    {
        return $this->database()->table('tag_expert_groups')->where('tag_id', $tagId)->orderBy('group_id')->pluck('group_id')->map(fn ($id) => (int) $id)->all();
    }

    private function approve(int $actor, mixed $groupId): \Psr\Http\Message\ResponseInterface
    {
        return $this->send($this->request('PATCH', '/api/tag-expert-suggestions/1', [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'tag-expert-suggestions', 'id' => '1', 'attributes' => ['status' => 'approved']], 'meta' => ['groupId' => $groupId]],
        ]));
    }

    private function pendingSuggestion(): void
    {
        $this->prepareDatabase(['tag_expert_suggestions' => [['id' => 1, 'tag_id' => 1, 'user_id' => 5, 'status' => 'pending', 'created_at' => Carbon::now()]]]);
    }

    public function test_admins_mods_e_ocultos_nao_viram_grupo_de_especialistas(): void
    {
        $this->send($this->request('PATCH', '/api/tags/2', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'tags', 'id' => '2', 'attributes' => ['expertGroupIds' => [1, 4, self::HIDDEN_GROUP, self::EXPERT_GROUP]]]],
        ]));

        $this->assertSame([self::EXPERT_GROUP], $this->groupsOfTag(2));
    }

    public function test_moderacao_nao_promove_ninguem_a_admin(): void
    {
        // Linha antiga, de antes da regra: o grupo Admins como especialista.
        $this->prepareDatabase(['tag_expert_groups' => [['tag_id' => 1, 'group_id' => 1]]]);
        $this->pendingSuggestion();

        $this->assertEquals(422, $this->approve(4, 1)->getStatusCode());
        $this->assertFalse($this->database()->table('group_user')->where(['user_id' => 5, 'group_id' => 1])->exists());
    }

    public function test_group_id_precisa_ser_inteiro(): void
    {
        $this->pendingSuggestion();

        $this->assertEquals(422, $this->approve(4, self::EXPERT_GROUP.'abc')->getStatusCode());
        $this->assertSame('pending', $this->database()->table('tag_expert_suggestions')->value('status'));
    }

    public function test_grupo_oculto_nao_aparece_nem_conta(): void
    {
        $this->prepareDatabase(['tag_expert_groups' => [['tag_id' => 1, 'group_id' => self::HIDDEN_GROUP]]]);
        $this->reply(7, 1);

        $tags = $this->json($this->send($this->request('GET', '/api/tags', ['authenticatedAs' => 2])))['data'];
        $this->assertSame([self::EXPERT_GROUP], collect($tags)->firstWhere('id', '1')['attributes']['expertGroupIds']);
        // A resposta de quem está no grupo oculto não marca a discussão.
        $this->assertNull($this->expertPostNumber(1));
    }

    public function test_lista_de_grupos_precisa_ser_lista_de_ids(): void
    {
        $response = $this->send($this->request('PATCH', '/api/tags/2', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'tags', 'id' => '2', 'attributes' => ['expertGroupIds' => ['a' => self::EXPERT_GROUP]]]],
        ]));

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame([], $this->groupsOfTag(2));
    }

    public function test_desfazer_o_endosso_nao_devolve_a_vaga_do_dia(): void
    {
        $this->setting('ramon-tag-experts.endorsements_per_day', 1);
        $id = $this->json($this->endorse(2, 5, 1))['data']['id'];

        $this->send($this->request('DELETE', '/api/tag-expert-endorsements/'.$id, ['authenticatedAs' => 2]));

        $this->assertEquals(422, $this->endorse(2, 6, 1)->getStatusCode());
    }

    public function test_desfazer_o_endosso_tira_a_sugestao_pendente(): void
    {
        $this->setting('ramon-tag-experts.endorsement_threshold', 1);
        $id = $this->json($this->endorse(2, 5, 1))['data']['id'];
        $this->assertSame(1, $this->database()->table('tag_expert_suggestions')->count());

        $this->send($this->request('DELETE', '/api/tag-expert-endorsements/'.$id, ['authenticatedAs' => 2]));

        $this->assertSame(0, $this->database()->table('tag_expert_suggestions')->count());
    }

    public function test_tag_invisivel_fica_fora_da_fila(): void
    {
        $this->pendingSuggestion();
        $this->database()->table('tags')->where('id', 1)->update(['is_restricted' => 1]);

        $list = $this->json($this->send($this->request('GET', '/api/tag-expert-suggestions', ['authenticatedAs' => 4])));
        $this->assertSame([], $list['data']);
        $this->assertEquals(404, $this->approve(4, self::EXPERT_GROUP)->getStatusCode());
    }

    public function test_excluir_o_grupo_recalcula(): void
    {
        $this->reply(3, 1);
        $this->assertSame(2, $this->expertPostNumber(1));

        $this->send($this->request('DELETE', '/api/groups/'.self::EXPERT_GROUP, ['authenticatedAs' => 1]));

        $this->assertNull($this->expertPostNumber(1));
    }

    public function test_excluir_a_pessoa_recalcula(): void
    {
        $this->reply(3, 1);

        $this->assertEquals(204, $this->send($this->request('DELETE', '/api/users/3', ['authenticatedAs' => 1]))->getStatusCode());

        $this->assertNull($this->expertPostNumber(1));
    }

    public function test_mudar_a_opcao_do_primeiro_post_recalcula(): void
    {
        $this->database()->table('posts')->where('id', 1)->update(['user_id' => 3]);

        $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => ['ramon-tag-experts.first_post_counts' => '1'],
        ]));

        $this->assertSame(1, $this->expertPostNumber(1));
    }

    public function test_gdpr_exporta_sugestoes_e_esquece_quem_revisou(): void
    {
        $this->prepareDatabase(['tag_expert_suggestions' => [
            ['id' => 1, 'tag_id' => 1, 'user_id' => 5, 'status' => 'dismissed', 'created_at' => Carbon::now(), 'resolved_by_id' => 4],
        ]]);
        $make = fn (int $id) => $this->app()->getContainer()->make(Endorsements::class, ['user' => User::query()->find($id), 'erasureRequest' => null]);

        $files = $make(5)->export();
        $this->assertNotNull($files);
        $this->assertContains('tag-experts/suggestions.json', array_merge(...array_map('array_keys', $files)));

        $make(4)->anonymize();
        $this->assertNull($this->database()->table('tag_expert_suggestions')->value('resolved_by_id'));
    }
}

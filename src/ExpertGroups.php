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

use Flarum\Group\Group;
use Psr\Http\Message\ServerRequestInterface;
use WeakMap;

/**
 * Os grupos de especialistas de todas as tags, lidos uma vez por
 * requisição: a lista de tags vai inteira no payload do fórum, e ler por
 * tag seria uma consulta para cada uma.
 */
class ExpertGroups
{
    /**
     * Grupos que nunca são de especialistas: Visitantes e Membros seriam todo
     * mundo, Admins e Mods dariam poder de equipe a quem for aprovado.
     */
    public const INELIGIBLE = [Group::ADMINISTRATOR_ID, Group::GUEST_ID, Group::MEMBER_ID, Group::MODERATOR_ID];

    /** @var WeakMap<ServerRequestInterface, array<int, list<int>>> */
    private WeakMap $cache;

    public function __construct(
        protected ExpertResolver $resolver
    ) {
        $this->cache = new WeakMap();
    }

    /**
     * @return list<int>
     */
    public function forTag(ServerRequestInterface $request, int $tagId): array
    {
        if (! isset($this->cache[$request])) {
            $map = [];
            foreach ($this->resolver->expertGroupRows()->get(['tag_expert_groups.tag_id', 'tag_expert_groups.group_id']) as $row) {
                $map[(int) $row->tag_id][] = (int) $row->group_id;
            }
            $this->cache[$request] = $map;
        }

        return $this->cache[$request][$tagId] ?? [];
    }
}

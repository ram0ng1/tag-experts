<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Console;

use Flarum\Console\AbstractCommand;
use Ramon\TagExperts\ExpertResolver;

/**
 * `php flarum tag-experts:recalculate` — refaz a resposta de especialista
 * de todas as discussões em tags com especialistas. Para depois de mexer
 * no banco à mão ou de instalar a extensão num fórum com histórico.
 */
class RecalculateCommand extends AbstractCommand
{
    public function __construct(
        protected ExpertResolver $resolver
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('tag-experts:recalculate')
            ->setDescription('Recalculate the first expert reply of every discussion in a tag with experts.');
    }

    protected function fire(): int
    {
        $tagIds = array_map('intval', array_keys($this->resolver->expertGroupsByTag($this->allTags())));
        $discussions = $tagIds ? $this->resolver->discussionsInTags($tagIds) : [];

        $this->resolver->recalculate($discussions);
        $this->info('Discussions recalculated: '.count($discussions).'.');

        return 0;
    }

    /** @return list<int> */
    private function allTags(): array
    {
        return \Flarum\Tags\Tag::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}

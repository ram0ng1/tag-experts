<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) {
            $table->boolean('accepts_expert_endorsements')->default(false);
        });

        // A primeira resposta de especialista, para o selo na lista e o filtro
        // sem consultar posts e grupos a cada página.
        $schema->table('discussions', function (Blueprint $table) {
            $table->unsignedInteger('expert_post_id')->nullable();
            $table->unsignedInteger('expert_post_number')->nullable();

            $table->foreign('expert_post_id')->references('id')->on('posts')->nullOnDelete();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->dropForeign(['expert_post_id']);
            $table->dropColumn(['expert_post_id', 'expert_post_number']);
        });

        $schema->table('tags', function (Blueprint $table) {
            $table->dropColumn('accepts_expert_endorsements');
        });
    },
];

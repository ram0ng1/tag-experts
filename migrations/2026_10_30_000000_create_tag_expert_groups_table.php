<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

// Grupos cujos membros são especialistas em cada tag.
return Migration::createTable('tag_expert_groups', function (Blueprint $table) {
    $table->unsignedInteger('tag_id');
    $table->unsignedInteger('group_id');

    $table->primary(['tag_id', 'group_id']);
    $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
    $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
});

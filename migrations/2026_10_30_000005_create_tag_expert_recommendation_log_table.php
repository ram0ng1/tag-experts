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

// Recomendações feitas nas últimas 24 horas, para o limite diário: desfazer
// uma recomendação apaga a linha dela, mas não esta.
return Migration::createTable('tag_expert_recommendation_log', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('user_id');
    $table->dateTime('created_at');

    $table->index(['user_id', 'created_at']);
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
});

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

return Migration::createTable('tag_expert_endorsements', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('tag_id');
    // Quem recebe e quem dá o endosso.
    $table->unsignedInteger('user_id');
    $table->unsignedInteger('endorser_id');
    $table->dateTime('created_at');

    $table->unique(['tag_id', 'user_id', 'endorser_id']);
    // Limite diário por quem endossa.
    $table->index(['endorser_id', 'created_at']);
    $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('endorser_id')->references('id')->on('users')->cascadeOnDelete();
});

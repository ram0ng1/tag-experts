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

// Membro que atingiu o número de endossos numa tag, esperando revisão.
return Migration::createTable('tag_expert_suggestions', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('tag_id');
    $table->unsignedInteger('user_id');
    $table->string('status', 16)->default('pending');
    $table->dateTime('created_at');
    $table->dateTime('resolved_at')->nullable();
    $table->unsignedInteger('resolved_by_id')->nullable();

    $table->unique(['tag_id', 'user_id']);
    $table->index('status');
    $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('resolved_by_id')->references('id')->on('users')->nullOnDelete();
});

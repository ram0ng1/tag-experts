<?php

/*
 * This file is part of ramon/tag-experts.
 *
 * Copyright (c) 2026 Ramon Guilherme.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ramon\TagExperts\Data;

use Flarum\Gdpr\Data\Type;
use Ramon\TagExperts\TagExpertEndorsement;
use Ramon\TagExperts\TagExpertSuggestion;

/**
 * Recomendações feitas e recebidas, e sugestões de especialista, no flarum/gdpr.
 */
class Endorsements extends Type
{
    public static function exportDescription(): string
    {
        return self::staticTranslator()->trans('ramon-tag-experts.lib.gdpr.export_description');
    }

    public static function anonymizeDescription(): string
    {
        return self::staticTranslator()->trans('ramon-tag-experts.lib.gdpr.anonymize_description');
    }

    public static function deleteDescription(): string
    {
        return self::staticTranslator()->trans('ramon-tag-experts.lib.gdpr.delete_description');
    }

    public function export(): ?array
    {
        $rows = TagExpertEndorsement::query()
            ->where('endorser_id', $this->user->id)
            ->orWhere('user_id', $this->user->id)
            ->orderBy('id')
            ->get()
            ->map(fn (TagExpertEndorsement $e) => [
                'tag_id' => $e->tag_id,
                'direction' => $e->endorser_id === $this->user->id ? 'given' : 'received',
                'created_at' => $e->created_at->toIso8601String(),
            ])
            ->all();

        $suggestions = TagExpertSuggestion::query()
            ->where('user_id', $this->user->id)
            ->orderBy('id')
            ->get()
            ->map(fn (TagExpertSuggestion $s) => [
                'tag_id' => $s->tag_id,
                'status' => $s->status,
                'created_at' => $s->created_at->toIso8601String(),
            ])
            ->all();

        $files = [];
        if ($rows) {
            $files[] = ['tag-experts/endorsements.json' => $this->encodeForExport($rows)];
        }
        if ($suggestions) {
            $files[] = ['tag-experts/suggestions.json' => $this->encodeForExport($suggestions)];
        }

        return $files ?: null;
    }

    public function anonymize(): void
    {
        $this->delete();
    }

    public function delete(): void
    {
        TagExpertEndorsement::query()->where('endorser_id', $this->user->id)->orWhere('user_id', $this->user->id)->delete();
        TagExpertSuggestion::query()->getConnection()->table('tag_expert_recommendation_log')->where('user_id', $this->user->id)->delete();
        TagExpertSuggestion::query()->where('user_id', $this->user->id)->delete();
        // Quem revisou deixa de constar nas sugestões dos outros.
        TagExpertSuggestion::query()->where('resolved_by_id', $this->user->id)->update(['resolved_by_id' => null]);
    }
}

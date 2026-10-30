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

use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quem é especialista onde, e qual a primeira resposta de especialista de
 * cada discussão.
 *
 * Especialista numa discussão é quem está num grupo de especialistas de
 * alguma das tags dela. A resposta conta se é um comentário visível a todos
 * (aprovado, não oculto, não privado); o primeiro post só conta se o admin
 * ligar a opção, porque quem abre a pergunta raramente é quem responde.
 */
class ExpertResolver
{
    public function __construct(
        protected ConnectionInterface $db,
        protected SettingsRepositoryInterface $settings,
        protected ExtensionManager $extensions
    ) {
    }

    /**
     * Ids dos grupos de especialistas de cada tag.
     *
     * @param list<int> $tagIds
     * @return array<int, list<int>>
     */
    public function expertGroupsByTag(array $tagIds): array
    {
        $out = [];

        foreach ($this->db->table('tag_expert_groups')->whereIn('tag_id', $tagIds)->get(['tag_id', 'group_id']) as $row) {
            $out[(int) $row->tag_id][] = (int) $row->group_id;
        }

        return $out;
    }

    /**
     * Recalcula a primeira resposta de especialista de cada discussão.
     *
     * @param list<int> $discussionIds
     */
    public function recalculate(array $discussionIds): void
    {
        foreach (array_chunk(array_values(array_unique($discussionIds)), 200) as $chunk) {
            $first = $this->expertPosts()
                ->whereIn('posts.discussion_id', $chunk)
                ->groupBy('posts.discussion_id')
                ->selectRaw($this->wrap('posts.discussion_id').' as discussion_id, min('.$this->wrap('posts.id').') as post_id')
                ->pluck('post_id', 'discussion_id');

            $numbers = Post::query()->whereIn('id', $first->values()->all())->pluck('number', 'id');

            foreach ($chunk as $discussionId) {
                $postId = isset($first[$discussionId]) ? (int) $first[$discussionId] : null;

                Discussion::query()->whereKey($discussionId)->update([
                    'expert_post_id' => $postId,
                    'expert_post_number' => $postId !== null ? (int) $numbers[$postId] : null,
                ]);
            }
        }
    }

    /**
     * Discussões de certas tags, para recalcular quando os grupos mudam.
     *
     * @param list<int> $tagIds
     * @return list<int>
     */
    public function discussionsInTags(array $tagIds): array
    {
        return $this->db->table('discussion_tag')->whereIn('tag_id', $tagIds)->distinct()->pluck('discussion_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Discussões, em tags com especialistas, em que alguém comentou: as que
     * podem mudar quando os grupos dessa pessoa mudam.
     *
     * @return list<int>
     */
    public function discussionsOf(int $userId): array
    {
        return Post::query()
            ->where('posts.user_id', $userId)
            ->where('posts.type', 'comment')
            ->whereIn('posts.discussion_id', fn ($query) => $query
                ->select('discussion_tag.discussion_id')
                ->from('discussion_tag')
                ->join('tag_expert_groups', 'tag_expert_groups.tag_id', '=', 'discussion_tag.tag_id'))
            ->distinct()
            ->pluck('posts.discussion_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Comentários públicos cujo autor está num grupo de especialistas de uma
     * tag da própria discussão.
     *
     * @return Builder<Post>
     */
    public function expertPosts(): Builder
    {
        $firstPostCounts = (bool) $this->settings->get('ramon-tag-experts.first_post_counts', false);

        return Post::query()
            ->where('posts.type', 'comment')
            ->whereNull('posts.hidden_at')
            ->where('posts.is_private', false)
            ->when(! $firstPostCounts, fn (Builder $query) => $query->where('posts.number', '>', 1))
            // A coluna só existe com o flarum/approval.
            ->when($this->extensions->isEnabled('flarum-approval'), fn (Builder $query) => $query->where('posts.is_approved', true))
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('group_user')
                ->join('tag_expert_groups', 'tag_expert_groups.group_id', '=', 'group_user.group_id')
                ->join('discussion_tag', 'discussion_tag.tag_id', '=', 'tag_expert_groups.tag_id')
                ->whereColumn('group_user.user_id', 'posts.user_id')
                ->whereColumn('discussion_tag.discussion_id', 'posts.discussion_id'));
    }

    /** O post conta como resposta de especialista? */
    public function isExpertPost(Post $post): bool
    {
        return $this->expertPosts()->whereKey($post->id)->exists();
    }

    private function wrap(string $column): string
    {
        return $this->db->getQueryGrammar()->wrap($column);
    }
}

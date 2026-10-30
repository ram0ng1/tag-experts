import app from 'flarum/common/app';
import extractText from 'flarum/common/utils/extractText';
import type Group from 'flarum/common/models/Group';
import type User from 'flarum/common/models/User';
import type Tag from 'ext:flarum/tags/common/models/Tag';

export function t(key: string, params: Record<string, unknown> = {}) {
  return app.translator.trans(`ramon-tag-experts.${key}`, params);
}

export function tText(key: string, params: Record<string, unknown> = {}): string {
  return extractText(t(key, params));
}

/** Grupos de especialistas da tag, como o servidor manda (ids em texto). */
export function expertGroupIds(tag: Tag): string[] {
  return ((tag.attribute<Array<string | number>>('expertGroupIds') || []) as Array<string | number>).map(String);
}

/**
 * Tags em que a pessoa é especialista, entre as informadas. Sai dos grupos
 * que já vêm com o post, sem pedir nada ao servidor.
 */
export function expertTags(user: User | null | undefined | false, tags: Array<Tag | undefined | false>): Tag[] {
  if (!user) return [];

  const groups = ((user.groups() || []) as Array<Group | undefined | false>).filter(Boolean).map((group) => (group as Group).id()!);

  if (!groups.length) return [];

  return tags.filter((tag): tag is Tag => !!tag && expertGroupIds(tag).some((id) => groups.includes(id)));
}

import app from 'flarum/common/app';
import Component from 'flarum/common/Component';
import Avatar from 'flarum/common/components/Avatar';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';
import username from 'flarum/common/helpers/username';
import type Group from 'flarum/common/models/Group';
import type Mithril from 'mithril';
import tagLabel from 'ext:flarum/tags/common/helpers/tagLabel';
import type Tag from 'ext:flarum/tags/common/models/Tag';

import type Suggestion from '../models/Suggestion';
import { expertGroupIds, t, tText } from '../utils/experts';

/**
 * A fila de sugestões de especialistas: quem recebeu recomendações suficientes
 * numa tag. A mesma lista serve ao admin e à página da moderação no fórum.
 */
export default class SuggestionList extends Component {
  suggestions: Suggestion[] = [];
  loading = true;
  moreResults = false;
  busy: Record<string, boolean> = {};
  chosenGroup: Record<string, string> = {};

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);

    this.load();
  }

  view() {
    if (this.loading && !this.suggestions.length) {
      return <LoadingIndicator />;
    }

    if (!this.suggestions.length) {
      return <p className="TagExpertSuggestions-empty">{t('lib.suggestions.empty')}</p>;
    }

    return (
      <div className="TagExpertSuggestions">
        <ul className="TagExpertSuggestions-list">{this.suggestions.map((suggestion) => this.item(suggestion))}</ul>
        {this.moreResults && (
          <Button className="Button" loading={this.loading} onclick={() => this.load(this.suggestions.length)}>
            {t('lib.suggestions.load_more_button')}
          </Button>
        )}
      </div>
    );
  }

  item(suggestion: Suggestion): Mithril.Children {
    const user = suggestion.user() || null;
    const tag = suggestion.tag() as Tag | false;
    const id = suggestion.id()!;
    const groups = tag ? this.groupsFor(tag) : [];
    const name = user ? tText('lib.suggestions.group_label', { username: user.displayName() }) : '';

    return (
      <li className="TagExpertSuggestion" key={id}>
        <div className="TagExpertSuggestion-user">
          <Avatar user={user} />
          <div className="TagExpertSuggestion-info">
            <a className="TagExpertSuggestion-name" href={user ? this.userUrl(user.slug()) : undefined} target="_blank" rel="noopener">
              {username(user)}
            </a>
            <span className="TagExpertSuggestion-meta">
              {tag ? t('lib.suggestions.in_tag', { tag: tagLabel(tag) }) : null}
              {' · '}
              {t('lib.suggestions.recommendations', { count: suggestion.endorsementCount() })}
            </span>
          </div>
        </div>

        <div className="TagExpertSuggestion-actions">
          {groups.length > 1 && (
            <Select
              className="TagExpertSuggestion-group"
              aria-label={name}
              value={this.chosenGroup[id] || groups[0].id()}
              options={Object.fromEntries(groups.map((group) => [group.id(), group.namePlural()]))}
              onchange={(value: string) => (this.chosenGroup[id] = value)}
            />
          )}
          <Button
            className="Button Button--primary"
            icon="fas fa-check"
            loading={this.busy[id]}
            disabled={!groups.length}
            title={!groups.length ? tText('lib.suggestions.no_group') : undefined}
            onclick={() => this.resolve(suggestion, 'approved', groups)}
          >
            {t('lib.suggestions.approve_button')}
          </Button>
          <Button className="Button" icon="fas fa-times" disabled={this.busy[id]} onclick={() => this.resolve(suggestion, 'dismissed', groups)}>
            {t('lib.suggestions.dismiss_button')}
          </Button>
        </div>
      </li>
    );
  }

  groupsFor(tag: Tag): Group[] {
    return expertGroupIds(tag)
      .map((id) => app.store.getById<Group>('groups', id))
      .filter((group): group is Group => !!group);
  }

  /** O admin não tem as rotas do fórum: o endereço do perfil sai da baseUrl. */
  userUrl(slug: string): string {
    return `${app.forum.attribute<string>('baseUrl')}/u/${encodeURIComponent(slug)}`;
  }

  async load(offset = 0) {
    this.loading = true;
    m.redraw();

    try {
      const results = await app.store.find<Suggestion[]>('tag-expert-suggestions', {
        include: 'user.groups,tag',
        page: { offset },
      });

      this.suggestions = offset ? [...this.suggestions, ...results] : results;
      this.moreResults = !!results.payload?.links?.next;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  async resolve(suggestion: Suggestion, status: 'approved' | 'dismissed', groups: Group[]) {
    const id = suggestion.id()!;
    const user = suggestion.user();
    const tag = suggestion.tag() as Tag | false;
    const groupId = this.chosenGroup[id] || groups[0]?.id();

    this.busy[id] = true;

    try {
      await suggestion.save({ status }, status === 'approved' ? { meta: { groupId: Number(groupId) } } : {});

      this.suggestions = this.suggestions.filter((item) => item !== suggestion);

      app.alerts.show(
        { type: 'success' },
        status === 'approved'
          ? t('lib.suggestions.approved_message', { username: user ? user.displayName() : '', tag: tag ? tag.name() : '' })
          : t('lib.suggestions.dismissed_message')
      );
    } finally {
      this.busy[id] = false;
      m.redraw();
    }
  }
}

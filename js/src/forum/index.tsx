import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Badge from 'flarum/common/components/Badge';
import Button from 'flarum/common/components/Button';
import LinkButton from 'flarum/common/components/LinkButton';
import Tooltip from 'flarum/common/components/Tooltip';
import Discussion from 'flarum/common/models/Discussion';
import CommentPost from 'flarum/forum/components/CommentPost';
import SessionDropdown from 'flarum/forum/components/SessionDropdown';
import UserControls from 'flarum/forum/utils/UserControls';
import classList from 'flarum/common/utils/classList';
import type Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type Tag from 'ext:flarum/tags/common/models/Tag';

import EndorseModal from './components/EndorseModal';
import addAvocadoBadges from './integrations/avocado';
import { expertTags, t, tText } from '../common/utils/experts';

export { default as extend } from './extend';

/** Tags da discussão em que o autor do post é especialista. */
function postExpertTags(post: Post): Tag[] {
  const discussion = post.discussion();
  const tags = discussion ? ((discussion as any).tags?.() as Tag[] | false) || [] : [];

  return post.contentType() === 'comment' && !post.isHidden() ? expertTags(post.user(), tags) : [];
}

app.initializers.add('ramon-tag-experts', () => {
  // O selo ao lado do nome e a classe no post; os grupos já vêm com o post.
  extend(CommentPost.prototype, 'headerItems', function (this: CommentPost, items: ItemList<Mithril.Children>) {
    const tags = postExpertTags(this.attrs.post);

    if (!tags.length) return;

    const tooltip = tText('forum.post.expert_tooltip', { tags: tags.map((tag) => tag.name()).join(', ') });

    items.add(
      'tagExpert',
      <Tooltip text={tooltip}>
        <span className="TagExpertBadge" tabindex="0" aria-label={tooltip}>
          <i className="fas fa-user-graduate" aria-hidden="true" />
          <span className="TagExpertBadge-label">{t('forum.post.expert_badge')}</span>
        </span>
      </Tooltip>,
      90
    );
  });

  extend(CommentPost.prototype, 'elementAttrs', function (this: CommentPost, attrs: Record<string, any>) {
    if (postExpertTags(this.attrs.post).length) {
      attrs.className = classList(attrs.className, 'Post--tagExpert');
    }
  });

  // Discussão respondida por especialista: selo na lista e na página.
  extend(Discussion.prototype, 'badges', function (this: Discussion, items: ItemList<Mithril.Children>) {
    if (this.attribute<number | null>('expertPostNumber')) {
      items.add(
        'tagExpertReply',
        <Badge type="tagExpertReply" icon="fas fa-user-graduate" label={t('forum.discussion.expert_reply_badge')} tabindex="0" />,
        5
      );
    }
  });

  extend(UserControls, 'userControls', function (items: ItemList<Mithril.Children>, ...args: unknown[]) {
    const user = args[0] as User;
    const actor = app.session.user;

    if (!actor || actor === user || !app.forum.attribute<boolean>('canEndorseTagExperts')) return;

    items.add(
      'tagExpertEndorse',
      <Button icon="fas fa-user-graduate" onclick={() => app.modal.show(EndorseModal, { user })}>
        {t('forum.user_controls.endorse_button')}
      </Button>
    );
  });

  extend(SessionDropdown.prototype, 'items', function (items: ItemList<Mithril.Children>) {
    if (!app.forum.attribute<boolean>('canReviewTagExperts')) return;

    items.add(
      'tagExpertSuggestions',
      <LinkButton icon="fas fa-user-graduate" href={app.route('tagExperts.suggestions')}>
        {t('forum.header.suggestions_button')}
      </LinkButton>,
      45
    );
  });

  // A grade de notificações é carregada sob demanda: extend pelo nome.
  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items: ItemList<any>) {
    items.add('tagExpertApproved', {
      name: 'tagExpertApproved',
      icon: 'fas fa-user-graduate',
      label: t('forum.settings.notify_approved_label'),
    });
  });

  addAvocadoBadges();
});

import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import type Tag from 'ext:flarum/tags/common/models/Tag';

import { t } from '../../common/utils/experts';

/** "Agora você é especialista em X", levando à tag. */
export default class BecameExpertNotification extends Notification {
  icon() {
    return 'fas fa-user-graduate';
  }

  href() {
    const tag = this.attrs.notification.subject() as Tag | undefined;

    return tag ? app.route('tag', { tags: tag.slug() }) : app.route('index');
  }

  content() {
    const tag = this.attrs.notification.subject() as Tag | undefined;

    return t('forum.notifications.approved_text', { tag: tag?.name() ?? '' });
  }

  excerpt() {
    return null;
  }
}

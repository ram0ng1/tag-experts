import app from 'flarum/common/app';
import { BooleanGambit } from 'flarum/common/query/IGambit';

/** `has:expert-reply`: discussões que um especialista da tag respondeu. */
export default class ExpertReplyGambit extends BooleanGambit {
  key(): string {
    return app.translator.trans('ramon-tag-experts.lib.gambits.expert_reply.key', {}, true);
  }

  booleanKey(): 'has' {
    return 'has';
  }

  filterKey(): string {
    return 'expertReply';
  }
}

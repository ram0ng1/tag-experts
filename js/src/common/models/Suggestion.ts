import Model from 'flarum/common/Model';
import type User from 'flarum/common/models/User';
import type Tag from 'ext:flarum/tags/common/models/Tag';

export default class Suggestion extends Model {
  status = Model.attribute<string>('status');
  endorsementCount = Model.attribute<number>('endorsementCount');
  createdAt = Model.attribute<Date | undefined, string | undefined>('createdAt', Model.transformDate);

  user = Model.hasOne<User>('user');
  tag = Model.hasOne<Tag>('tag');
}

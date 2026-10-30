import Model from 'flarum/common/Model';
import type User from 'flarum/common/models/User';
import type Tag from 'ext:flarum/tags/common/models/Tag';

/** Uma recomendação feita por quem está logado: a API só devolve as próprias. */
export default class Endorsement extends Model {
  createdAt = Model.attribute<Date | undefined, string | undefined>('createdAt', Model.transformDate);

  user = Model.hasOne<User>('user');
  tag = Model.hasOne<Tag>('tag');
}

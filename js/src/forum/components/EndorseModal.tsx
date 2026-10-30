import app from 'flarum/forum/app';
import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type User from 'flarum/common/models/User';
import type Mithril from 'mithril';
import tagIcon from 'ext:flarum/tags/common/helpers/tagIcon';
import sortTags from 'ext:flarum/tags/common/utils/sortTags';
import type Tag from 'ext:flarum/tags/common/models/Tag';

import type Endorsement from '../../common/models/Endorsement';
import { expertTags, t } from '../../common/utils/experts';

export interface EndorseModalAttrs extends IFormModalAttrs {
  user: User;
}

/**
 * Marca as tags em que a pessoa manda bem. Desmarcar apaga o endosso; os
 * endossos são privados, então só aparecem os de quem está logado.
 */
export default class EndorseModal extends FormModal<EndorseModalAttrs> {
  tags: Tag[] = [];
  existing: Record<string, Endorsement> = {};
  selected: Set<string> = new Set();
  ready = false;

  oninit(vnode: Mithril.Vnode<EndorseModalAttrs, this>) {
    super.oninit(vnode);

    this.load();
  }

  className() {
    return 'TagExpertEndorseModal Modal--small';
  }

  title() {
    return t('forum.endorse_modal.title', { username: this.attrs.user.displayName() });
  }

  content() {
    if (!this.ready) {
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    }

    return (
      <div className="Modal-body">
        {this.tags.length ? (
          <Form>
            <p className="helpText">{t('forum.endorse_modal.text')}</p>
            <div className="Form-group TagExpertEndorseModal-tags">
              {this.tags.map((tag) => {
                const id = tag.id()!;

                return (
                  <label className="checkbox" key={id}>
                    <input
                      type="checkbox"
                      checked={this.selected.has(id)}
                      onchange={(e: Event) => this.toggle(id, (e.target as HTMLInputElement).checked)}
                    />
                    {tagIcon(tag, { className: 'TagExpertEndorseModal-icon' })}
                    {tag.name()}
                  </label>
                );
              })}
            </div>
            <div className="Form-group Form-controls">
              <Button type="submit" className="Button Button--primary" loading={this.loading}>
                {t('forum.endorse_modal.submit')}
              </Button>
            </div>
          </Form>
        ) : (
          <p className="helpText">{t('forum.endorse_modal.no_tags')}</p>
        )}
      </div>
    );
  }

  async load() {
    const user = this.attrs.user;

    const [tags, endorsements] = await Promise.all([
      (app as any).tagList.load(['parent']) as Promise<Tag[]>,
      app.store.find<Endorsement[]>('tag-expert-endorsements', { filter: { user: user.id()! } }),
    ]);

    // Quem já é especialista na tag não precisa de endosso nela.
    const expertIn = new Set(expertTags(user, tags).map((tag) => tag.id()));

    this.tags = sortTags(tags.filter((tag) => tag.attribute<boolean>('acceptsExpertEndorsements') && !expertIn.has(tag.id())));

    endorsements.forEach((endorsement) => {
      const tag = endorsement.tag();
      if (tag) this.existing[tag.id()!] = endorsement;
    });

    this.selected = new Set(Object.keys(this.existing));
    this.ready = true;
    m.redraw();
  }

  toggle(id: string, checked: boolean) {
    checked ? this.selected.add(id) : this.selected.delete(id);
  }

  async onsubmit(e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;

    const added = this.tags.filter((tag) => this.selected.has(tag.id()!) && !this.existing[tag.id()!]);
    const removed = Object.keys(this.existing).filter((id) => !this.selected.has(id));

    try {
      // Um por vez: o limite diário é contado no servidor, e o erro de um não
      // deve esconder os que já entraram.
      for (const tag of added) {
        const endorsement = app.store.createRecord<Endorsement>('tag-expert-endorsements');
        await endorsement.save({ relationships: { user: this.attrs.user, tag } });
        this.existing[tag.id()!] = endorsement;
      }

      for (const id of removed) {
        await this.existing[id].delete();
        delete this.existing[id];
      }

      this.hide();
      app.alerts.show({ type: 'success' }, t('forum.endorse_modal.saved'));
    } catch (error) {
      this.loading = false;
      m.redraw();
    }
  }
}

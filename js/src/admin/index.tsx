import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import Stream from 'flarum/common/utils/Stream';
import Group from 'flarum/common/models/Group';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

import { expertGroupIds, t } from '../common/utils/experts';

export { default as extend } from './extend';

const MODAL = 'ext:flarum/tags/admin/components/EditTagModal';

app.initializers.add('ramon-tag-experts', () => {
  extend(MODAL, 'oninit', function (this: any) {
    this.tagExpertGroups = Stream<string[]>(this.tag.exists ? expertGroupIds(this.tag) : []);
    this.tagExpertEndorsements = Stream<boolean>(!!this.tag.attribute('acceptsExpertEndorsements'));
  });

  // Logo antes da ordenação padrão, junto das outras opções da tag.
  extend(MODAL, 'fields', function (this: any, items: ItemList<Mithril.Children>) {
    // Visitantes e Membros seriam todo mundo; o servidor também os recusa.
    const groups = app.store.all<Group>('groups').filter((group) => ![Group.GUEST_ID, Group.MEMBER_ID].includes(group.id()!));
    const selected: string[] = this.tagExpertGroups();

    const toggle = (id: string, checked: boolean) => {
      this.tagExpertGroups(checked ? [...selected, id] : selected.filter((item) => item !== id));
    };

    items.add(
      'tagExpertGroups',
      <div className="Form-group TagExpertGroups">
        <label>{t('admin.edit_tag.expert_groups_label')}</label>
        <div className="helpText">{t('admin.edit_tag.expert_groups_help')}</div>
        {groups.map((group) => (
          <label className="checkbox" key={group.id()}>
            <input
              type="checkbox"
              checked={selected.includes(group.id()!)}
              onchange={(e: Event) => toggle(group.id()!, (e.target as HTMLInputElement).checked)}
            />
            {group.namePlural()}
          </label>
        ))}
      </div>,
      7
    );

    items.add(
      'tagExpertEndorsements',
      <div className="Form-group">
        <label className="checkbox">
          <input type="checkbox" bidi={this.tagExpertEndorsements} />
          {t('admin.edit_tag.accepts_endorsements_label')}
        </label>
      </div>,
      6
    );
  });

  extend(MODAL, 'submitData', function (this: any, data: Record<string, unknown>) {
    data.expertGroupIds = this.tagExpertGroups().map(Number);
    data.acceptsExpertEndorsements = this.tagExpertEndorsements();
  });
});

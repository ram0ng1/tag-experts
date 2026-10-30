import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import PageStructure from 'flarum/forum/components/PageStructure';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import type Mithril from 'mithril';

import SuggestionList from '../../common/components/SuggestionList';
import { t, tText } from '../../common/utils/experts';

/** A fila de sugestões para a moderação, no layout das páginas do fórum. */
export default class SuggestionsPage extends Page {
  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);

    if (!app.forum.attribute<boolean>('canReviewTagExperts')) {
      m.route.set(app.route('index'));
      return;
    }

    app.history.push('tagExperts.suggestions', tText('lib.suggestions.heading'));
    app.setTitle(tText('lib.suggestions.heading'));
    app.setTitleCount(0);

    this.bodyClass = 'App--tagExpertSuggestions';
  }

  view() {
    return (
      <PageStructure className="TagExpertSuggestionsPage" sidebar={() => <IndexSidebar />}>
        <h2 className="TagExpertSuggestionsPage-title">{t('lib.suggestions.heading')}</h2>
        <SuggestionList />
      </PageStructure>
    );
  }
}

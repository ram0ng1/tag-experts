import Extend from 'flarum/common/extenders';
import FieldSet from 'flarum/common/components/FieldSet';

import commonExtend from '../common/extend';
import SuggestionList from '../common/components/SuggestionList';
import { t } from '../common/utils/experts';

export default [
  ...commonExtend,

  new Extend.Admin()
    .setting(() => ({
      setting: 'ramon-tag-experts.badge_style',
      type: 'select',
      options: {
        label: t('admin.settings.badge_style_options.label'),
        icon: t('admin.settings.badge_style_options.icon'),
      },
      default: 'label',
      label: t('admin.settings.badge_style_label'),
      help: t('admin.settings.badge_style_help'),
    }))
    .setting(() => ({
      setting: 'ramon-tag-experts.endorsement_threshold',
      type: 'number',
      min: 1,
      label: t('admin.settings.threshold_label'),
      help: t('admin.settings.threshold_help'),
    }))
    .setting(() => ({
      setting: 'ramon-tag-experts.endorsements_per_day',
      type: 'number',
      min: 1,
      label: t('admin.settings.per_day_label'),
    }))
    .setting(() => ({
      setting: 'ramon-tag-experts.first_post_counts',
      type: 'boolean',
      label: t('admin.settings.first_post_counts_label'),
    }))
    // A fila fica abaixo das configurações; não é um campo do formulário.
    .customSetting(
      () => (
        <FieldSet className="TagExpertSuggestionsSetting" label={t('lib.suggestions.heading')}>
          <p className="helpText">{t('admin.settings.suggestions_help')}</p>
          <SuggestionList />
        </FieldSet>
      ),
      -100
    )
    .permission(
      () => ({
        icon: 'fas fa-user-graduate',
        label: t('admin.permissions.recommend_label'),
        permission: 'tagExperts.endorse',
      }),
      'reply'
    )
    .permission(
      () => ({
        icon: 'fas fa-user-check',
        label: t('admin.permissions.review_label'),
        permission: 'tagExperts.review',
      }),
      'moderate'
    ),
];

import Extend from 'flarum/common/extenders';

import commonExtend from '../common/extend';
import BecameExpertNotification from './components/BecameExpertNotification';
import SuggestionsPage from './components/SuggestionsPage';

export default [
  ...commonExtend,

  new Extend.Routes() //
    .add('tagExperts.suggestions', '/tag-experts/suggestions', SuggestionsPage),

  new Extend.Notification() //
    .add('tagExpertApproved', BecameExpertNotification),
];

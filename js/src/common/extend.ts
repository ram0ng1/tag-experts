import Extend from 'flarum/common/extenders';

import Endorsement from './models/Endorsement';
import Suggestion from './models/Suggestion';
import ExpertReplyGambit from './query/ExpertReplyGambit';

export default [
  new Extend.Store() //
    .add('tag-expert-endorsements', Endorsement)
    .add('tag-expert-suggestions', Suggestion),

  new Extend.Search() //
    .gambit('discussions', ExpertReplyGambit),
];

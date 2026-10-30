# Tag Experts

Recognise the members who know a tag best on your Flarum 2 forum. Inspired by
Discourse's [category experts plugin](https://github.com/discourse/discourse-category-experts).

- **Expert groups per tag**: in **Tags → edit tag**, pick the groups whose
  members are experts in that tag. Guests and Members can't be picked, and
  neither can Admins, Mods or hidden groups: approving a suggestion adds the
  member to the group, and a badge would reveal who is in a hidden group.
- **Expert replies stand out**: their posts get an *Expert* badge next to the
  name and a thin accent bar, in any discussion tagged with that tag.
- **"Has an expert reply" badge**: discussions answered by an expert show a
  badge in the list and on the discussion page. Search them with
  `has:expert-reply` (or `-has:expert-reply` for the unanswered ones).
- **Endorsements**: members can endorse someone as an expert in the tags that
  accept endorsements (**Members can endorse experts in this tag** in the tag
  modal). Endorsements are private, and each member has a daily limit.
- **Review queue**: when someone reaches the endorsement threshold, they show
  up in **Expert suggestions** for moderators: in the user menu on the forum
  and on the extension page in the admin. Approving adds them to the tag's
  expert group and notifies them; dismissing keeps them from coming back.
- **Works with**: flarum/approval (unapproved posts don't count until they
  are approved), flarum/gdpr (endorsements are exported and erased), and the
  `ramon/canela` and `ramon/avocado` themes.

## Settings

| Setting | Default |
| --- | --- |
| Endorsements needed for a suggestion | 3 |
| Endorsements per member per day | 3 |
| The first post of a discussion counts as an expert reply | off |

Permissions: **Endorse members as tag experts** (members) and **Review expert
suggestions** (moderators).

## How an expert reply is found

A discussion's expert reply is its first visible comment written by a member
of one of the expert groups of the discussion's tags. It's stored on the
discussion and updated when posts are created, hidden, restored, deleted or
approved, when the discussion's tags change, when a tag's expert groups
change and when someone's groups change, so lists and searches never compute
it on the fly.

After changing groups outside Flarum (straight in the database, for example),
rebuild it with:

```bash
php flarum tag-experts:recalculate
```

## Installation

```bash
composer require ramon/tag-experts
php flarum migrate
php flarum cache:clear
```

## Updating

```bash
composer update ramon/tag-experts
php flarum migrate
php flarum cache:clear
```

## Links

- [Source](https://github.com/ram0ng1/tag-experts)
- [Issues](https://github.com/ram0ng1/tag-experts/issues)

## License

MIT

<p align="center">
  <img src="icon.svg" width="80" height="80" alt="Tag Experts">
</p>

<h1 align="center">Tag Experts</h1>

<p align="center">
  <a href="https://github.com/ram0ng1/tag-experts/actions/workflows/ci.yml"><img alt="CI" src="https://img.shields.io/github/actions/workflow/status/ram0ng1/tag-experts/ci.yml?branch=main&style=flat-square&label=ci"></a>
  <a href="https://packagist.org/packages/ramon/tag-experts"><img alt="Packagist" src="https://img.shields.io/packagist/v/ramon/tag-experts?style=flat-square&label=packagist"></a>
  <a href="https://packagist.org/packages/ramon/tag-experts"><img alt="Downloads" src="https://img.shields.io/packagist/dt/ramon/tag-experts?style=flat-square"></a>
  <img alt="Flarum" src="https://img.shields.io/badge/flarum-2.x-e7672e?style=flat-square">
  <a href="LICENSE"><img alt="License" src="https://img.shields.io/badge/license-MIT-blue?style=flat-square"></a>
  <a href="https://donate.stripe.com/fZe5o66nebkf39S28a"><img alt="Donate" src="https://img.shields.io/badge/donate-stripe-6772E5?style=flat-square"></a>
</p>

<p align="center">Recognise the users who know a tag best.</p>

Tag Experts lets you pick, for each tag, the groups whose users are experts in it. Their replies get an *Expert* badge next to the username, and discussions they answer are marked as having an expert reply, so readers find the trusted answer at a glance. Inspired by Discourse's [category experts plugin](https://github.com/discourse/discourse-category-experts).

Experts can also come from the community. Users recommend someone as an expert in the tags that allow it, and when enough of them agree, the person shows up in a review queue for the moderators, who approve or dismiss the suggestion.

## What it does

- Expert groups per tag, chosen in the tag's edit modal
- *Expert* badge to the right of the username, as icon and text or icon only with a tooltip
- "Has an expert reply" badge in the discussion list and page, and the `has:expert-reply` search filter (`-has:expert-reply` for the unanswered ones)
- Private recommendations with a daily limit, and an **Expert Suggestions** review queue in the user menu and in the admin
- Notification by alert when someone is made an expert
- Plays nice with `flarum/approval` (unapproved posts only count once approved) and `flarum/gdpr` for export, anonymization and erasure
- Fits the core look and the `ramon/canela` and `ramon/avocado` themes
- Built for Flarum 2.x (PHP 8.3+), with flarum/tags

## Installation

```sh
composer require ramon/tag-experts
php flarum migrate
php flarum cache:clear
```

Then enable Tag Experts on the Extensions page, pick the expert groups in **Tags → edit tag**, and tune the badge, the recommendation threshold and the daily limit in the extension settings. Permissions: **Recommend users as tag experts** (members by default) and **Review expert suggestions** (moderators by default).

## Good to know

- Guests, Members, Admins, Mods and hidden groups can't be expert groups: approving a suggestion adds the user to the group, and the badge would reveal who is in a hidden group.
- A discussion's expert reply is its first visible comment by a user of one of the expert groups of its tags. It is stored on the discussion and kept up to date when posts, tags, groups or users change, so lists and searches never compute it on the fly.
- After changing groups outside Flarum (straight in the database, for example), rebuild it with `php flarum tag-experts:recalculate`.

## Development

```sh
composer install
composer test:setup        # once: creates the SQLite test database
composer test              # unit + integration tests
composer analyse:phpstan
cd js && npm ci && npm run check-typings && npm run format-check && npm run build
```

## License

[MIT](LICENSE). Questions and ideas are welcome on the [issue tracker](https://github.com/ram0ng1/tag-experts/issues).

# TODO

Source: MVP plan. Check items off as they ship.

## Foundations

- [x] Laravel, Livewire, Tailwind, Horizon, Telescope, Redis cache and queue
- [x] Light and dark theme tokens with a theme switcher
- [x] Localized routes (English default, `/fi` for Finnish) and a language switcher
- [x] Translation files for English and Finnish (started: `lang/fi.json`)
- [x] Pest guards in `tests/Pest.php` (stray requests, Sleep, Exceptions, Vite)
- [ ] Service name and domain

## Content pages

- [ ] About
- [ ] FAQ
- [ ] Privacy policy (Hetzner Finland, SES EU, Plausible, 90 day retention)
- [x] Cookie consent banner installed (whitecube, `dev-main`, Finnish translation)
- [ ] Cookies page (the banner policy link in `config/cookieconsent.php`)
- [ ] Switch whitecube from `dev-main` to a tagged release once one supports Laravel 13
- [ ] Test `wire:navigate` with localized routes once there are Livewire pages

## Poll core (MVP)

- [x] Data model: polls, options, votes, vote choices
- [x] Phase derived from timestamps, manual close via `closed_at`
- [ ] Create a poll without an account (title, description, category, voting method)
- [ ] Share link and secret admin link (admin token stored hashed)
- [ ] Voting: single choice, multiple choice, ranking (Borda, full or Top N chosen by the creator)
- [ ] Voter token cookie, votes can be changed while the poll is open
- [ ] Rate limits for voting and poll creation
- [ ] Datetime options with timezone: creator picks a zone, voters see their own zone
- [ ] Results page with winner, ties and totals
- [ ] Admin: edit, close, delete, copy links
- [ ] Scheduled cleanup 90 days after a poll closes (pruning is done: `model:prune`; still to show the date in the form and admin view)
- [ ] `noindex` on all poll pages
- [x] Scoring logic (`App\Scoring\Tally`) with unit tests

## Live results

- [ ] Live page with `wire:poll.visible`, results cached in Redis (short TTL, `Cache::flexible`)
- [ ] Throttle on live routes
- [ ] Hide live results from participants (creator setting)

## After the MVP core

- [ ] Community suggestions round (own start and end, duplicate guard, moderation)
- [ ] Games category (IGDB, cached metadata)
- [ ] Books category (Open Library)
- [ ] OBS-friendly live view and secret live link
- [ ] Open Graph preview image for results
- [ ] Translatable poll content (spatie/laravel-translatable) if needed

## Deferred (not in the MVP)

- [ ] Email: send the admin link to the creator once, without storing the address (Amazon SES EU)
- [ ] Stricter voter protection (one-time codes or personal links)
- [ ] Optional accounts, own poll overview
- [ ] Public poll directory, password protected polls, embeddable widgets
- [ ] Ranked choice (instant runoff), partial ranking
- [ ] Movies, TV and Music categories

## Infrastructure

- [ ] Hetzner server (Finland), Redis, Horizon under a process supervisor
- [ ] Self-hosted Plausible, script served from our own domain
- [ ] Amazon SES (EU region), when email is added

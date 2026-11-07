# Changes & New Features

## Vendor Payouts Switched to USD

- Vendor earnings are now paid at **$0.001 per stream** (`vendor_rate_per_stream`).
- Vendor wallets, earnings, and withdrawals are denominated in USD; listener/rental rewards remain in Naira.
- Existing vendor wallet balances and pending payouts are reset to zero by the migration.
- Vendor withdrawals use `vendor_min_payout` (default **$1.00**) and are converted to Naira at `usd_ngn_rate` (default **1,344.50**) when sent to Paystack.
- Vendor APK **v1.0.4** displays USD amounts and supports fractional payouts (published to `public/vendor-download/`).
- Vendor APK **v1.0.5** hardens device identity (hardware ID + no Android backup) so cloned/restored app data cannot make two phones share one fleet device or FCM token.
- Vendor APK **v1.0.6** fixes the identity-migration ordering so a restored phone regenerates its FCM token on the first launch after updating.
- Vendor APK **v1.0.7** adds persistent background execution, FCM token retry/re-registration, and heartbeat-driven pending-assignment recovery copied from the private controller app.
- Vendor APK **v1.0.8** adds the private app's permission panel (notifications, battery exemption, display over other apps, media control), background service toggle, keep-awake switch, background/earnings status card, and a working update download progress bar.
- Vendor APK **v1.0.9** enables keep-awake by default and adds shortcuts to Android screen-timeout and screen-lock settings.
- Device registration now tolerates re-keying/duplicate FCM tokens: `devices.fcm_token` is nullable and old/new fleet rows are merged, so phones no longer disappear from the vendor fleet after re-registering.
- Vendor heartbeats now recreate or restore a missing/soft-deleted fleet device automatically, so a phone cannot go invisible while it keeps checking in.
- The `/device-assignments` countdown is now drift-resistant, fires at 00:00 even when the page loads late, retries failed advances, and shares a server lock with the campaign worker so a track cannot advance twice.
- Track advance no longer sends a stop command before the next play. That stale stop was being reported after the new track was already written, marking the new assignment `stopped` and ending the campaign after one track.
- Run `php artisan migrate` after deploying so the vendor rate, app version gate, and reset take effect.

## Feature 1: Interstitial Content
Play content outside the playlist after X tracks, then return to the playlist.

### New Campaign Fields (UI)
- **Interstitial toggle** — enable/disable in campaign create/edit
- **Frequency** — "Play interstitial after every N playlist tracks"
- **Media URL** — the interstitial track/video to play
- **Duration** — how long to play the interstitial before resuming

### How it works
- `device_assignments.cycle_track_count` increments on each normal track advance
- When it reaches `campaigns.interstitial_every`, the system plays `interstitial_media_url` instead of the next playlist track
- `device_assignments.is_interstitial` is set to `true`
- On the next advance tick (when interstitial duration expires), the system resumes the playlist at the next track and resets `cycle_track_count` to 0

### Files changed
- `database/migrations/2026_06_10_000001_add_interstitial_and_channel_fields.php`
- `app/Console/Commands/ExecuteCampaigns.php` — interstitial logic in `processSingleAssignment()`
- `app/Http/Controllers/Api/AssignmentController.php` — interstitial logic in `nextTrack()`
- `app/Http/Controllers/CampaignController.php` — store/update accept interstitial fields

---

## Feature 2: Random Starting Track Per Device
When a campaign is deployed to multiple phones, each device starts at a **different random track** from the shuffled playlist.

### How it works
- Each device gets a deterministic shuffle based on `device_id + campaign_id + assigned_at` (same seed formula used by `ExecuteCampaigns`)
- A random starting index is picked per device using `abs(crc32(seed . '_start')) % trackCount`
- `shuffled_index` is stored on the assignment and used by both the cron command and the `nextTrack` endpoint

### Files changed
- `app/Http/Controllers/CampaignController.php` — `deploy()` picks per-device random start
- `app/Console/Commands/ExecuteCampaigns.php` — uses `shuffled_index` instead of searching by track ID
- `app/Http/Controllers/Api/AssignmentController.php` — uses `shuffled_index` + deterministic shuffle
- `app/Models/DeviceAssignment.php` — `shuffled_index` added to fillable

---

## Feature 3: YouTube Channel Mode
Mark tracks as **channel videos** or **similar videos** from other creators. The interstitial system interleaves them naturally.

### New Campaign Fields (UI)
- **Channel URL** — YouTube channel link (shown only when platform = YouTube)
- **Track Type dropdown** per track: `Playlist` / `Channel` / `Similar`

### How it works
- Tracks are tagged with `track_type` = `playlist`, `channel_video`, or `similar_video`
- Combined with the interstitial system: set "play interstitial every N tracks" and use similar videos as interstitial content
- Campaign cards display track type icons (YT logo for channel, users icon for similar)

### Display
- Track rows show `CH` or `SIM` badges with color coding
- Campaign list shows a channel badge when `channel_url` is set
- Campaign cards show interstitial interval info

### Files changed
- `database/migrations/2026_06_10_000001_add_interstitial_and_channel_fields.php`
- `resources/views/dashboard.blade.php` — campaign cards & track list
- `resources/views/_partials/modals.blade.php` — edit campaign modal
- `resources/views/_partials/scripts.blade.php` — create & edit campaign JS

---

## Database Changes (Migration)

| Table | Column | Type | Default |
|-------|--------|------|---------|
| `campaigns` | `channel_url` | varchar(191) nullable | null |
| `campaigns` | `interstitial_every` | unsigned int nullable | null |
| `campaigns` | `interstitial_media_url` | varchar(191) nullable | null |
| `campaigns` | `interstitial_duration_seconds` | unsigned int | 120 |
| `campaign_tracks` | `track_type` | varchar(20) | 'playlist' |
| `device_assignments` | `shuffled_index` | unsigned int | 0 |
| `device_assignments` | `cycle_track_count` | unsigned int | 0 |
| `device_assignments` | `is_interstitial` | boolean | false |

## No Android/APK Changes Needed
All features are server-side only. The FCM payload is unchanged — devices just receive `play` commands with URLs as before.

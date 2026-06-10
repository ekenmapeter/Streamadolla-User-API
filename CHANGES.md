# Changes & New Features

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

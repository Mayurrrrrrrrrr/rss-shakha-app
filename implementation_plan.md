# Implementation Plan: Native Offline-First RSS Shakha App

Transition the current PHP WebView wrapper into a high-performance, native Flutter application. The application will adopt an offline-first strategy where all reads and writes target a local SQLite database, and changes are synchronized bi-directionally with the MySQL backend when internet access is available.

## User Review Required

> [!IMPORTANT]
> **Database Schema Upgrades:**
> Incremental delta sync requires adding `updated_at` timestamps to all target tables in the backend MySQL database. A migration script (`api/migrate_sync.php`) will add these columns with `ON UPDATE CURRENT_TIMESTAMP`.
> Deletion tracking requires either soft deleting or creating a `deleted_records` changelog table. We propose using an `is_active = 0` soft delete flag or a lightweight changelog table `sync_deletes` to let the app know when items are removed.

> [!TIP]
> **Visual Identity ("Indian-Minimalist"):**
> The UI will use a curated palette featuring Deep Saffron (`#FF6B00`), Warm Amber (`#FFB300`), Charcoal Gray, and Soft Cream. Typography will utilize Google Fonts (`Noto Sans Devanagari` and `Outfit`) for premium look-and-feel.

## Open Questions

> [!WARNING]
> **Session & Authorization Strategy:**
> The current PHP backend uses traditional session cookies (`$_SESSION`). To authorize REST calls from the native Flutter client, we recommend either:
> 1. Generating and returning a long-lived JWT/token upon successful credentials validation in `api/login.php`.
> 2. Continuing to use session cookies by preserving cookie storage in the Flutter client (`dio_cookie_manager`).
> *Recommendation: Session-based cookie persistence for minimum changes on the server, or JWT for standard mobile setups.*

---

## Proposed Changes

### Component 1: Backend Database & Synchronization Endpoints

To support delta-pulls and queue pushes:
- Implement delta detection columns on MySQL tables.
- Build incremental synchronization PHP endpoints returning standard JSON.

#### [NEW] [migrate_sync.php](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/api/migrate_sync.php)
A database migration script to:
1. Add `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` to tables: `shakhas`, `swayamsevaks`, `daily_records`, `attendance`, `activities`, `daily_activities`, `timetable_defaults`, `timetable_overrides`, `events`, `subhashits`, `amrit_vachan`, `geet`, `ghoshnayein`.
2. Add a `sync_deletes` table to track deleted item IDs: `table_name VARCHAR(50)`, `deleted_id INT`, `deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`.
3. Update delete endpoints to record deletions in `sync_deletes`.

#### [NEW] [pull.php](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/api/sync/pull.php)
JSON REST API that accepts a `last_sync_timestamp` and `shakha_id`:
- Returns delta changes since `last_sync_timestamp` for all tables associated with the specified `shakha_id` (or global items).
- Returns list of deleted IDs from `sync_deletes` since `last_sync_timestamp`.
- Responds with standard REST JSON response structure.

#### [NEW] [push.php](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/api/sync/push.php)
Batch write handler endpoint:
- Accepts array of synced action records (e.g. daily records created offline).
- Performs transactional insertion/updating of bulk records.
- Returns status lists for each action item.

---

### Component 2: Flutter App Dependencies & Configuration

Update dependencies to support local database storage, state management, HTTP requests, connectivity monitoring, and date-time localization.

#### [MODIFY] [pubspec.yaml](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/pubspec.yaml)
Add packages:
- `flutter_riverpod`: State management
- `dio`: Networking
- `sqflite`: Local SQLite storage
- `path_provider` & `path`: File directory access
- `connectivity_plus`: Network status monitoring
- `shared_preferences`: Storing session configuration & sync timestamps
- `intl`: Localized date formatting
- `google_fonts`: Dynamic high-quality Hindi font support
- `uuid`: Generating offline item primary keys

---

### Component 3: Flutter Core Database & Networking Services

Establish the foundation for offline-first capabilities: database structures and network layers.

#### [NEW] [database_helper.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/core/db/database_helper.dart)
SQLite controller using `sqflite`:
- Initializes local DB tables: `shakhas`, `swayamsevaks`, `daily_records`, `attendance`, `activities`, `daily_activities`, `timetable_defaults`, `timetable_overrides`, `events`, `subhashits`, `amrit_vachan`, `geet`, `ghoshnayein`.
- Configures an `offline_actions_queue` table: `id` (UUID), `action` (e.g. 'save_record'), `endpoint`, `payload` (JSON text), `created_at`.
- Implements standard CRUD transactional helper APIs.

#### [NEW] [api_client.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/core/api/api_client.dart)
Dio-based network engine:
- Configures interceptors to inject Session/JWT tokens.
- Manages base URLs and error handling templates.

#### [NEW] [sync_engine.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/core/sync/sync_engine.dart)
Bi-directional synchronization manager:
1. **Push:** Read queue items from `offline_actions_queue` chronologically, POST to server, delete on success.
2. **Pull:** Fetch `last_sync_timestamp` from preferences, request delta from `/api/sync/pull.php`, insert/replace items in local DB, update `last_sync_timestamp`.
3. Monitors network connection using `connectivity_plus` to auto-trigger push queues.

---

### Component 4: Flutter Native UI Features

Translate PHP-driven pages into custom native Material 3 widgets with smooth transitions and responsive layouts.

#### [MODIFY] [main.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/main.dart)
App initialization wrapper:
- Boots up Riverpod and initializes local SQLite database.
- Routes to `LoginScreen` or `NavigationShell` depending on local login status.
- Sets global themes (Saffron and Sand/Cream colors).

#### [NEW] [login_screen.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/features/auth/login_screen.dart)
Native login interface:
- Simple, premium visual card with devanagari titles.
- Validates credentials on `api/login.php`, stores user credentials locally, and boots sync logic.

#### [NEW] [dashboard_screen.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/features/dashboard/dashboard_screen.dart)
Premium homepage dashboard:
- Displays sync status indicator (e.g. green/red indicator light with last updated text).
- Includes the Hindu Panchang widgets (Yugabdh, Vikram/Shaka Samvat, Tithi) parsed from cached server parameters.
- Quick navigation tiles: Swayamsevak directory, Attendance record, Timetable, Timer, and Content Library.

#### [NEW] [daily_record_screen.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/features/records/daily_record_screen.dart)
Daily Attendance and activity entry form:
- Swayamsevak list with checkbox selection.
- List of activities with toggle switches and drop-downs for conductor names.
- Automatic local DB save. Triggers sync engine if online, otherwise inserts action into the sync queue.

#### [NEW] [swayamsevak_screen.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/features/swayamsevaks/swayamsevak_screen.dart)
Swayamsevak directory:
- Native search, filtering by age category (Bal, Tarun, Praudh, etc.), and Gat (Group).
- Modal sheet to add or edit Swayamsevaks offline.

#### [NEW] [shakha_timer_screen.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/features/shakha/shakha_timer_screen.dart)
Interactive meeting timer:
- Visual clock widget.
- Pre-configured timer configurations for prayers, physical training, and reading based on default settings.
- Plays alert sound on phase completion.

#### [NEW] [content_screen.dart](file:///c:/Users/mayur/.gemini/antigravity/scratch/rss-shakha-app/flutter_app/lib/features/content/content_screen.dart)
Premium content browser:
- Tabs for Subhashit, Amrit Vachan, Geet, and Ghoshnayein.
- Renders text natively with rich formatting.
- Offline search bar.

---

## Verification Plan

### Automated Tests
- Build and execution verification:
  ```bash
  cd flutter_app
  flutter test
  ```

### Manual Verification
1. **Offline Insertion:** Start app, disable Wi-Fi/data connection on device. Go to Swayamsevak directory, create a new record. Verify it is saved locally in SQLite and added to the offline actions queue.
2. **Synchronization:** Re-enable internet connection. Verify that the sync engine fires, pushes the queued record to the MySQL backend database, and clears the queue item from the local SQLite database.
3. **Pull Sync:** Change database contents on the server (e.g. update a Subhashit). Trigger pull sync from the dashboard. Confirm the changes render instantly in the content library.

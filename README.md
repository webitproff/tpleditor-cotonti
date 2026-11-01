# Template Editor (tpleditor) for Cotonti

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/webitproff/tpleditor-cotonti)
[![Cotonti](https://img.shields.io/badge/Cotonti%201.0-orange.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.5%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-lightgrey.svg)](https://github.com/webitproff/tpleditor-cotonti/blob/main/License.txt)

A plugin for Cotonti CMF that adds a full-featured `.tpl` file editor to the admin panel — for themes, admin themes, plugins and modules — powered by CodeMirror 5 with syntax highlighting, bracket matching and AJAX saving without page reload. No core modifications — everything goes through standard hooks `tools` and `ajax`.

---
<img width="1920" height="1080" alt="Template Editor (tpleditor) для Cotonti_2026-11-01 11 48 00 (5)" src="https://github.com/user-attachments/assets/79c2ffc5-2ab4-4776-a7bc-30d299c5efc5" />
<img width="1920" height="1080" alt="Template Editor (tpleditor) для Cotonti_2026-11-01 11 48 00" src="https://github.com/user-attachments/assets/b35f9461-fdd0-45b7-b376-577026d37e2e" />
<img width="1920" height="1080" alt="Template Editor (tpleditor) для Cotonti_2026-11-01 11 48 00 (2)" src="https://github.com/user-attachments/assets/77a2881a-e4d2-4483-b327-a515b274d549" />
<img width="1920" height="1080" alt="Template Editor (tpleditor) для Cotonti_2026-11-01 11 48 00 (3)" src="https://github.com/user-attachments/assets/41c62734-a719-473a-a31b-d1354ec61d34" />
<img width="1920" height="1080" alt="Template Editor (tpleditor) для Cotonti_2026-11-01 11 48 00 (4)" src="https://github.com/user-attachments/assets/6e19921d-6a2f-483f-87d8-0d667ac30a68" />

---


## 📑 Table of Contents

- [1. Overview](#1-overview)
  - [1.1. What is Template Editor](#11-what-is-template-editor)
  - [1.2. Key Features](#12-key-features)
  - [1.3. System Requirements](#13-system-requirements)
  - [1.4. Compatibility](#14-compatibility)
  - [1.5. Links](#15-links)
- [2. How It Works](#2-how-it-works)
  - [2.1. Architecture](#21-architecture)
  - [2.2. Plugin Structure](#22-plugin-structure)
  - [2.3. Hooks and Their Role](#23-hooks-and-their-role)
  - [2.4. How the File List Works](#24-how-the-file-list-works)
  - [2.5. How the Editor Works](#25-how-the-editor-works)
  - [2.6. URL Parameters](#26-url-parameters)
  - [2.7. Security](#27-security)
- [3. Step-by-Step Installation](#3-step-by-step-installation)
  - [3.1. Download](#31-download)
  - [3.2. Installation via FTP/SFTP](#32-installation-via-ftpsftp)
  - [3.3. Activation in Admin Panel](#33-activation-in-admin-panel)
  - [3.4. Permissions Setup](#34-permissions-setup)
  - [3.5. Verification](#35-verification)
- [4. Step-by-Step Usage Guide](#4-step-by-step-usage-guide)
  - [4.1. File List](#41-file-list)
  - [4.2. Opening a File in the Editor](#42-opening-a-file-in-the-editor)
  - [4.3. Saving Changes](#43-saving-changes)
  - [4.4. Downloading a Backup Copy](#44-downloading-a-backup-copy)
  - [4.5. Keyboard Shortcuts](#45-keyboard-shortcuts)
- [5. Plugin Configuration](#5-plugin-configuration)
- [6. Step-by-Step Security Guide](#6-step-by-step-security-guide)
- [7. Troubleshooting](#7-troubleshooting)
- [8. Logs and Diagnostics](#8-logs-and-diagnostics)
- [9. FAQ](#9-faq)
- [10. Plugin Update](#10-plugin-update)
- [11. Plugin Uninstall](#11-plugin-uninstall)
- [12. Roadmap](#12-roadmap)
- [13. Changelog](#13-changelog)
- [14. Contributing](#14-contributing)
- [15. License](#15-license)
- [16. Contacts and Support](#16-contacts-and-support)

---

## 1. Overview

### 1.1. What is Template Editor

**Template Editor** is a plugin for [Cotonti CMF](https://github.com/Cotonti/Cotonti) that lets an administrator view and edit any `.tpl` file directly from the admin panel — no FTP/SFTP, no ssh:

- **CodeMirror 5 editor** — HTML/JS/CSS highlighting, line numbers, bracket matching;
- **file list** — all `.tpl` files from themes, admin themes, plugins, modules and core in one place;
- **AJAX saving** — no page reload, with a status indicator;
- **backup download** — one click to download the current version of any file.

The plugin installs as a standard Cotonti extension and **does not require core modifications**. All logic works through two hooks: `tools` and `ajax`.

### 1.2. Key Features

- **Unified file list** — all editable `.tpl` files with root labels ("Theme", "Plugin: foo", "Core admin").
- **CodeMirror editor** — `eclipse` theme, `htmlmixed` mode, line numbers, match/autoClose brackets, `indentUnit=4`.
- **Resizable wrapper** — the editor container stretches vertically (`resize: vertical`), ResizeObserver refreshes CodeMirror when the size changes.
- **fetch-based saving** — POST to `index.php?r=tpleditor` with `action=save`, `file`, `content`, `x` (CSRF).
- **Download** — GET link to `action=download` with the CSRF token passed via `x`.
- **Marker-loss protection** — if the source contained `<!-- BEGIN: -->` and the new content does not — saving is rejected.
- **Cotonti cache clearing** — `Cot::$cache->clear()` is called after a successful save.
- **Multi-language** — `ru` and `en` language files.
- **No core modifications** — only `tools` and `ajax` hooks.

### 1.3. System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP       | 8.0     | 8.5+        |
| MySQL     | 8.0     | 8.4+        |
| MariaDB   | 10.2    | 10.6+       |
| Cotonti   | 1.0.x   | 1.0.x       |

**PHP extensions:**
- `pdo_mysql` — required;
- `mbstring` — for UTF-8 and Cyrillic;
- `json` — built-in since PHP 8.0.

**File permissions:**
- theme/plugin/module directories and `.tpl` files must be readable by the web-server user;
- for saving — writable (`0664` for files, `0775` for directories; Cotonti defaults).

**Client (browser):**
- any modern browser with `fetch`, `URLSearchParams`, `ResizeObserver` support (Chrome/Edge ≥ 64, Firefox ≥ 69, Safari ≥ 13.1).

### 1.4. Compatibility

Tested on:

- **Cotonti** 1.0.x;
- **MySQL** 8.4;
- **MariaDB** 10.2 — 10.11;
- **PHP** 8.5;
- **Apache** 2.4, **Nginx** (with php-fpm), **LiteSpeed**.

### 1.5. Links

- **Cotonti Marketplace:** [abuyfile.com/market/cotonti/plugs/tpleditor](https://abuyfile.com/market/cotonti/plugs/tpleditor)
- **GitHub Repository:** [github.com/webitproff/tpleditor-cotonti](https://github.com/webitproff/tpleditor-cotonti)
- **Support Forum:** [abuyfile.com/forums/cotonti/custom/](https://abuyfile.com/forums/cotonti/custom/)
- **CodeMirror:** [codemirror.net/5/](https://codemirror.net/5/)

---

## 2. How It Works

### 2.1. Architecture

The plugin consists of two hook files, one function file and two templates. No schema changes to the database beyond the records added to `cot_core`, `cot_auth`, `cot_config` and `cot_plugins` during installation (standard set).

Flow:

1. The user opens `/admin.php?m=other&p=tpleditor`.
2. The Router (`system/router/Router.php::routeAdminOther`) picks up `p=tpleditor` and locates `tpleditor.tools.php` via the `tools` hook.
3. **Without a `file` parameter** — the plugin builds a list of all `.tpl` files (walking `themes/`, `plugins/`, `modules/`, `system/admin/tpl`) and renders `tpleditor.tools.tpl`.
4. **With a `file=<rel>` parameter** — it validates the path via `tpleditor_validate_path()`, reads the content and renders `tpleditor.editor.tpl` with CodeMirror.
5. **Saving** — fetch POST to `index.php?r=tpleditor` → `tpleditor.ajax.php` with `action=save`.
6. **Download** — GET to the same AJAX endpoint with `action=download`.

### 2.2. Plugin Structure

```
plugins/tpleditor/
├── tpleditor.setup.php              # plugin metadata + config
├── tpleditor.tools.php              # tools hook: admin page
├── tpleditor.ajax.php               # ajax hook: save / read / download
├── inc/
│   └── tpleditor.functions.php      # business logic (API)
├── tpl/
│   ├── tpleditor.tools.tpl          # file list
│   └── tpleditor.editor.tpl         # CodeMirror editor
└── lang/
    ├── tpleditor.ru.lang.php
    └── tpleditor.en.lang.php
```

### 2.3. Hooks and Their Role

| Hook   | Entry point | What it does |
|--------|-------------|--------------|
| `tools` | `admin.php?m=other&p=tpleditor` | Modes: file list / CodeMirror editor |
| `ajax`  | `index.php?r=tpleditor` | Actions: `save`, `read`, `download` |

**Important:** hooks are declared **in the hook files themselves** via the `[BEGIN_COT_EXT]` block. The setup file contains only metadata (`Code`, `Name`, `Version`, `Auth_*`, `Lock_*`) and the config block `[BEGIN_COT_EXT_CONFIG]` with four settings `edit_*`.

### 2.4. How the File List Works

1. The user opens `/admin.php?m=other&p=tpleditor` without a `file` parameter.
2. `tpleditor_roots()` builds the list of allowed roots — depending on config:
   - `edit_themes` → `themes/<theme>/` (current user theme);
   - `edit_admin` → `themes/admin/<admintheme>/` (if `admintheme` is non-empty);
   - `edit_plugins` → `plugins/<code>/tpl/` for each plugin that has a `tpl/` subdirectory;
   - `edit_modules` → `modules/<code>/tpl/` for each module that has a `tpl/` subdirectory;
   - **always** → `system/admin/tpl/` (core admin templates).
3. `tpleditor_list_files()` recursively walks each root, collects `.tpl` files, sorts by `rel` via `strcasecmp`.
4. Each row contains: `rel` (path), `size` (bytes), `mtime` (date), `label` (root label).
5. The `<a href="{FILE_URL}">` link goes to the same URL with `?file=<rel>`.

### 2.5. How the Editor Works

1. The user clicks a file → `/admin.php?m=other&p=tpleditor&file=<rel>`.
2. `tpleditor_validate_path($file)` returns either `null` (error) or an absolute `realpath`.
3. The file content is read via `file_get_contents()` and passed to the `{FILE_CONTENT}` tag (after `htmlspecialchars`).
4. The `tpleditor.editor.tpl` template creates `<textarea id="tpleditor-source">` and initializes `CodeMirror.fromTextArea` on it.
5. The "Save" button calls `save()` — fetch POST to the AJAX endpoint with `x` (CSRF), `a=save`, `file`, `content=editor.getValue()`.
6. JSON response: `{success: bool, message: string, bytes?: int}`.
7. The status indicator next to the buttons shows "Saved (N B)" or "Error: ...".

### 2.6. URL Parameters

| Parameter | Value | Description |
|-----------|-------|-------------|
| `m`      | `other` | "Other" section in admin |
| `p`      | `tpleditor` | plugin code |
| `file`   | string | relative path to `.tpl` (e.g. `themes/mytheme/header.tpl`) |

AJAX requests:

| Parameter | Value | Description |
|-----------|-------|-------------|
| `r`      | `tpleditor` | plugin code for the router |
| `a`      | `save` \| `read` \| `download` | action |
| `file`   | string | relative path to `.tpl` |
| `content`| string | content (only for `save`, POST) |
| `x`      | string | CSRF token `Cot::$sys['xk']` |

### 2.7. Security

- **Double authorization barrier:**
  - `system/admin/admin.other.php` before include checks `cot_auth('plug','tpleditor','A')` and redirects to 403;
  - inside `tpleditor.tools.php` and `tpleditor.ajax.php` — `cot_auth('admin','a','A') || cot_auth('plug','tpleditor','A')`.
  The third argument `'A'` is required: when the mask is longer than 1 character, `cot_auth()` returns an array `[bool, bool, bool]` which is always truthy in PHP. An explicit `'A'` mask gives a scalar `bool`.
- **CSRF:**
  - `cot_check_xg(false)` for GET (`download`);
  - `cot_check_xp()` for POST (`save`, `read`).
- **Path validation `tpleditor_validate_path()`:**
  - the file must end in `.tpl`;
  - no `\0`, empty segments, `.` or `..`;
  - the path prefix must match one of the allowed roots;
  - `realpath(file)` and `realpath(root)` must be within `baseDir` — symlink escape protection.
- **`htmlspecialchars`** — applied to `FILE_REL` and `FILE_CONTENT` before injecting into HTML.
- **Marker check** — on save, if the source had `<!-- BEGIN: -->` and the new content has none — writing is rejected.
- **`Content-Type: application/octet-stream` + `X-Content-Type-Options: nosniff`** — for downloads; the browser does not try to render `.tpl` as HTML.

---

## 3. Step-by-Step Installation

### 3.1. Download

**Download** the archive from [GitHub](https://github.com/webitproff/tpleditor-cotonti) or from the [marketplace](https://abuyfile.com/market/cotonti/plugs/tpleditor).

### 3.2. Installation via FTP/SFTP

1. Back up your site and database.
2. Unpack the downloaded archive locally.
3. Upload the `tpleditor/` folder to `/plugins/` of your Cotonti site.
4. Verify the structure:

   ```
   /plugins/tpleditor/tpleditor.setup.php
   /plugins/tpleditor/tpleditor.tools.php
   /plugins/tpleditor/tpleditor.ajax.php
   /plugins/tpleditor/inc/tpleditor.functions.php
   /plugins/tpleditor/tpl/tpleditor.tools.tpl
   /plugins/tpleditor/tpl/tpleditor.editor.tpl
   /plugins/tpleditor/lang/tpleditor.ru.lang.php
   /plugins/tpleditor/lang/tpleditor.en.lang.php
   ```

### 3.3. Activation in Admin Panel

1. Log in to the Cotonti admin panel.
2. Go to **Extensions → Plugin Manager**.
3. Find **Template Editor** (`tpleditor`) in the list.
4. Click **"Install"**.

After installation the plugin appears in the **Tools** section (`admin.php?m=other`) under the name "Template Editor".

### 3.4. Permissions Setup

By default (`Auth_members=''`, `Lock_members='RW12345A'` in the setup file), only the **super-admin** (`COT_GROUP_SUPERADMINS`, id=5) has access to the plugin. Group permission changes via `admin.php?m=rights` are blocked — `Lock=255` locks all bits.

To expand access:

**Via SQL** (the only way when `Lock=255`):

```sql
-- Grant group "Administrators" the A right on tpleditor
UPDATE cot_auth
SET auth_rights = 128
WHERE auth_code = 'plug' AND auth_option = 'tpleditor' AND auth_groupid = 6;
```

Or **before installing the plugin** edit `tpleditor.setup.php`:

```ini
Auth_members=RWA
Lock_members=
```

and reinstall the plugin.

### 3.5. Verification

1. Open **Tools → Template Editor** (`admin.php?m=other&p=tpleditor`).
2. Verify that the `.tpl` file list appears with labels "Theme", "Admin theme", "Plugin: ...", "Core admin".
3. Click any theme file → the CodeMirror editor opens.
4. Change a couple of characters in a comment, click **"Save"** — the status should show "Saved (N B)".
5. Click **"Download backup copy"** — the browser downloads the current file version.

---

## 4. Step-by-Step Usage Guide

### 4.1. File List

When you open `admin.php?m=other&p=tpleditor` you see:

- heading "Template Editor";
- a `.tpl` file list sorted by path;
- each row: icon, `<code>` with path, root badge, size in bytes and modification date on the right.

The whole row is clickable — it opens the editor.

### 4.2. Opening a File in the Editor

Clicking a file opens the editor page:

- heading "Editing" + file path;
- button panel: **"Back to list"**, **"Save"**, **"Download backup copy"**, status indicator;
- the CodeMirror area itself with highlighting and line numbers.

The editor container can be dragged from its bottom edge (`resize: vertical`) — height varies from 200px to 90vh.

### 4.3. Saving Changes

1. Edit the content in CodeMirror.
2. Click **"Save"** or press `Ctrl+S` / `Cmd+S`.
3. The indicator next to the buttons shows "..." → "Saved (N B)" or "Error: <text>".

**What happens on the server:**
- CSRF check (`cot_check_xp`);
- path validation (`tpleditor_validate_path`);
- marker check (`<!-- BEGIN: -->` in source → `<!-- BEGIN: -->` in new content);
- `file_put_contents`;
- `Cot::$cache->clear()`.

On rejection — the file on disk is unchanged, the error appears in the indicator.

### 4.4. Downloading a Backup Copy

The **"Download backup copy"** button opens a GET link to the AJAX endpoint with `action=download` in a new tab. The server sends the file with headers:

- `Content-Type: application/octet-stream`
- `Content-Disposition: attachment; filename="<basename>"`
- `Content-Length: <size>`
- `X-Content-Type-Options: nosniff`

**Important:** this downloads the current state of the file, not a backup from history. The plugin does **not** create `.bak` files — make backups yourself before editing.

### 4.5. Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+S` / `Cmd+S` | Save file |
| `Tab` | Increase indent |
| `Shift+Tab` | Decrease indent |

The browser's default "Save page" on `Ctrl+S` is suppressed.

---

## 5. Plugin Configuration

Settings are available in **Extensions → Plugin Manager → Template Editor → Configure** (`admin.php?m=config&n=edit&o=plug&p=tpleditor`):

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `edit_themes`  | radio (0/1) | 1 | Allow editing current user theme `.tpl` files |
| `edit_admin`   | radio (0/1) | 1 | Allow editing admin theme `.tpl` files |
| `edit_plugins` | radio (0/1) | 1 | Allow editing plugin `.tpl` files |
| `edit_modules` | radio (0/1) | 1 | Allow editing module `.tpl` files |

The core root `system/admin/tpl/` is included **always**, regardless of settings.

Changes apply immediately: `tpleditor_flag()` reads `Cot::$cfg['plugin']['tpleditor'][<name>]` every time `tpleditor_roots()` is called.

---

## 6. Step-by-Step Security Guide

The plugin grants **direct access to server files**, so it demands discipline:

1. **Do not grant the `A` right on tpleditor to anyone.** The plugin can corrupt site templates to the point of breaking the site. Only trusted administrators.
2. **Back up regularly.** The plugin **does not create backups** — if you save a corrupted file, rollback is only from an external source (git, manual archive, previously downloaded "Download" copy).
3. **Use HTTPS.** `.tpl` content and the CSRF token are sent in POST; over HTTP they are visible in plaintext.
4. **Check `Lock_members` / `Lock_guests` in the setup file.** Defaults are `RW12345A` (full lock), preventing permission changes through the UI. Expand consciously.
5. **Do not disable CSRF checks.** `cot_check_xg` / `cot_check_xp` in `tpleditor.ajax.php` are the only barrier against CSRF attacks from third-party sites.
6. **Verify `Content-Type: application/octet-stream`** on download. Do not remove the `nosniff` header — otherwise the browser may try to render `.tpl` as HTML and execute inline JS.
7. **Restrict `/plugins/` access at the web server.** `.tpl` files should not be directly accessible over HTTP (only via the plugin). Use `deny from all` / `location ~ \.tpl$ { deny all; }`.
8. **Check template markers before saving.** The built-in `<!-- BEGIN: -->` protection saves you from accidentally saving an empty file, but not from deliberate corruption.

---

## 7. Troubleshooting

### 7.1. Plugin not appearing under "Tools"

**Cause:** plugin installed but the `tools` hook is not registered.

**Fix:**
- Check **Extensions → Plugin Manager** — the status must be "Active".
- Check `cot_plugins` in DB: `SELECT * FROM cot_plugins WHERE pl_code='tpleditor'`. There must be at least two records — `pl_part='tools'`, `pl_part='ajax'`.
- If missing — **Update** the plugin via admin (`Extensions → tpleditor → Update`).

### 7.2. 403 Forbidden on open

**Cause:** the user lacks `A` on `plug/tpleditor` or `admin/a`.

**Fix:**
- Check the user's group: `SELECT * FROM cot_auth WHERE auth_code='plug' AND auth_option='tpleditor' AND auth_groupid=<id>`.
- If `auth_rights < 128` — grant `A` via SQL (`UPDATE` to 128) or reinstall after editing the setup file.

### 7.3. Editor not loading (empty `<textarea>`)

**Cause:** CodeMirror failed to load (CDN down, CSP blocking `cdn.jsdelivr.net`).

**Fix:**
- Open the browser console — you'll see 404/CSP errors on jsdelivr requests.
- Download CodeMirror 5.65.16 locally and replace links in `tpleditor.editor.tpl` with local ones.
- Or allow `cdn.jsdelivr.net` in the site's CSP.

### 7.4. Save returns "Invalid path"

**Cause:** `tpleditor_validate_path()` rejected the path. Possible reasons:

- File does not end in `.tpl`.
- Path contains `..`, empty segments, `\0`.
- File is a symlink pointing outside `baseDir` or outside the allowed root.
- Directory from `tpleditor_roots()` does not exist (user theme deleted, plugin disabled).

**Fix:** check the file's `realpath` (`realpath /path/to/file` in ssh) and ensure it starts with `/full/path/to/baseDir/themes/<theme>/` or similar.

### 7.5. Save returns "Content rejected: template markers missing"

**Cause:** marker-loss protection. The source contained `<!-- BEGIN: ... -->` but the new content does not.

**Fix:**
- Ensure you haven't removed all BEGIN markers from the `.tpl`.
- If the file is not a Cotonti template (e.g., a `.tpl` for something else) — save it via FTP, or remove the check in `tpleditor.ajax.php` (not recommended).

### 7.6. "File not writable"

**Cause:** the web server has no write permission.

**Fix:**
- Check permissions: `ls -la /path/to/file.tpl` — must be `rw-` for the PHP-FPM user.
- Check ownership: `chown www-data:www-data /path/to/file.tpl` (or whichever user PHP runs as).
- Also make sure the directory is writable (`file_put_contents` needs access to the directory).

### 7.7. Download opens file in browser instead of downloading

**Cause:** browser ignores `Content-Disposition: attachment` or does MIME-sniffing.

**Fix:**
- Check that `X-Content-Type-Options: nosniff` is present (in `tpleditor.ajax.php`, action=download).
- Check that `Content-Type: application/octet-stream` (not `text/html`).

### 7.8. File list is empty

**Cause:** all `edit_*` are off, or directories don't exist.

**Fix:**
- Check plugin settings — all four flags should be `Yes`.
- Check that you have at least one theme (`themes/<theme>/`), and that `Cot::$usr['theme']` is an existing directory.
- The core root (`system/admin/tpl/`) is always present — if it's also missing from the list, something more serious is going on (plugin not installed correctly).

---

## 8. Logs and Diagnostics

### 8.1. Where to look

| What | Where |
|------|-------|
| PHP errors | `/var/log/php/error.log`, site `error_log` |
| MySQL errors | `/var/log/mysql/error.log` |
| Cotonti debug | `datas/config.php` → `$cfg['debug_mode'] = true` |
| AJAX responses | DevTools → Network → filter by `r=tpleditor` |

### 8.2. Enabling Debug

In `datas/config.php`:

```php
$cfg['debug_mode'] = true;
$cfg['devmode']    = true;
```

The admin panel will show a debug panel with SQL queries including plugin queries.

**Warning:** never leave debug on in production.

### 8.3. AJAX Responses

Open DevTools → Network, filter by `tpleditor`. On save you'll see a POST to `index.php?r=tpleditor`. The response is JSON:

```json
{
  "success": true,
  "message": "Saved",
  "bytes": 1234
}
```

On error:

```json
{
  "success": false,
  "message": "Invalid CSRF token"
}
```

HTTP status is 200 (for `catch`) or 400/403 (for explicit rejects). See changelog for v1.0.0.

---

## 9. FAQ

**Q: Does the plugin work offline?**
A: Only if CodeMirror is deployed locally. By default resources are loaded from `cdn.jsdelivr.net`; without internet the editor doesn't initialize.

**Q: Does the plugin create `.bak` files?**
A: No. Since v1.0.0 the creation of `.bak.YmdHis` files is **disabled**. The current file state can be downloaded via the "Download backup copy" button — but that's a snapshot, not history.

**Q: Can changes be rolled back?**
A: Only manually: a previously downloaded copy, git history, site backup. The plugin stores no history.

**Q: Does it work on Cotonti Siena 0.9.x?**
A: No. The plugin uses `Cot::$cfg`, `Cot::$sys`, `Cot::$usr` — the Cotonti 1.0.x API. It would need a rewrite for 0.9.x.

**Q: Does the plugin support editing `.php`?**
A: No. `tpleditor_validate_path()` requires the `.tpl` extension — the plugin does not allow editing PHP files.

**Q: What happens when the plugin is uninstalled?**
A: Records in `cot_core`, `cot_auth`, `cot_config`, `cot_plugins` are deleted. Plugin files remain on disk until removed manually. All `.tpl` edited through the plugin remain modified.

**Q: Can it be used on multiple sites?**
A: Yes, the plugin is not tied to a domain.

**Q: Why does only the super-admin have access by default?**
A: `Auth_members=''`, `Lock_members='RW12345A'` — a deliberate fail-safe default. Administrators must explicitly grant access via SQL or by reinstalling with an edited setup file (see section 3.4).

**Q: Why is the `read` action needed if templates don't call it?**
A: It's a fallback. In the current plugin templates the `read` action is not called — content arrives via `{FILE_CONTENT}` on server render. But the endpoint is ready for external integrations.

**Q: What's the `<!-- BEGIN: -->` check on save?**
A: Protection against accidentally saving an empty/truncated file. If the source had at least one Cotonti `<!-- BEGIN: -->` marker and the new content has none — the server refuses to write.

---

## 10. Plugin Update

1. **Back up** the plugin files and (just in case) the database.
2. Download the new version from the marketplace or GitHub.
3. In the Cotonti admin panel → **Extensions → Plugin Manager → Template Editor → Update**.
4. Verify that the `tools` and `ajax` hooks remain active.
5. Open `admin.php?m=other&p=tpleditor` and check that it works.

**Important:** if the version in the setup file matches the installed one, Cotonti returns `COT_EXT_NOTHING_TO_UPDATE` and no update is performed. For a forced update use **Update** with `force_update=true` (via `Extensions → details`), or bump `Version` in the setup file.

---

## 11. Plugin Uninstall

1. In the Cotonti admin panel → **Extensions → Plugin Manager → Template Editor → Uninstall**.
2. Delete the `plugins/tpleditor/` folder manually if it remains.
3. Clear the Cotonti cache.

**Important:** the plugin stores no data of its own in the DB. Uninstall is safe. All `.tpl` files edited via the plugin remain modified — to roll them back, use site backups.

---

## 12. Roadmap

- [ ] **Save history** — `.bak` files with a retention limit (e.g., last 5).
- [ ] **Diff against original** — diff between the current and downloaded state.
- [ ] **Content search** — grep across all `.tpl` from the list.
- [ ] **Create / delete** `.tpl` files via UI.
- [ ] **Edit `*.tpl` in plugin subdirectories** (currently only `tpl/`).
- [ ] **Core templates** — expose `modules/<code>/tpl/` as an optional root.
- [ ] **Pug/Twig support** — extend the list of supported extensions.
- [ ] **CodeMirror dark theme** — toggle between `eclipse` / `dracula`.
- [ ] **Autosave** — timer-based with an indicator.
- [ ] **REST API** — editing via API without UI.

---

## 13. Changelog

### v1.0.0 (2026-11-01)

- First public release.
- `tools` (admin page) and `ajax` (save/read/download) hooks.
- CodeMirror 5.65.16 with `eclipse` theme and `htmlmixed` mode.
- File list for themes, admin themes, plugins, modules and core.
- Path validation with symlink-escape protection.
- CSRF: `cot_check_xg` (GET) / `cot_check_xp` (POST).
- Marker check `<!-- BEGIN: -->` on save.
- Cotonti cache clearing after save.
- `ru` / `en` localization.
- Config: `edit_themes`, `edit_admin`, `edit_plugins`, `edit_modules`.
- Creation of `.bak.YmdHis` files **disabled** — backups only via the "Download backup copy" button.

---

## 14. Contributing

Pull requests are welcome. Before submitting:

1. Fork the [repository](https://github.com/webitproff/tpleditor-cotonti).
2. Create a branch `feature/your-feature` or `fix/your-bug`.
3. Follow PSR-12.
4. All public functions are prefixed `tpleditor_`.
5. All SQL parameters — via `Cot::$db->prep()` or binding.
6. All user data in HTML — via `htmlspecialchars()`.
7. Multi-language — via `$L['tpleditor_*']`.
8. Update `README.md` and `CHANGELOG`.

---

## 15. License

BSD 3-Clause. Full text in `License.txt`.

---

## 16. Contacts and Support

- **Cotonti Marketplace:** [abuyfile.com/market/cotonti/plugs/tpleditor](https://abuyfile.com/market/cotonti/plugs/tpleditor)
- **GitHub Repository:** [github.com/webitproff/tpleditor-cotonti](https://github.com/webitproff/tpleditor-cotonti)
- **Support Forum:** [abuyfile.com/forums/cotonti/custom/](https://abuyfile.com/forums/cotonti/custom/)
- **Author:** [webitproff](https://github.com/webitproff)
- **Issues:** [github.com/webitproff/tpleditor-cotonti/issues](https://github.com/webitproff/tpleditor-cotonti/issues)

___
> RU
> Русский
__

# Template Editor (tpleditor) для Cotonti

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/webitproff/tpleditor-cotonti)
[![Cotonti](https://img.shields.io/badge/Cotonti%201.0-orange.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.5%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-lightgrey.svg)](https://github.com/webitproff/tpleditor-cotonti/blob/main/License.txt)

Плагин для Cotonti CMF, добавляющий в админ-панель полноценный редактор `.tpl`-файлов темы, админ-темы, плагинов и модулей на базе CodeMirror 5 с подсветкой синтаксиса, парными скобками и сохранением через AJAX без перезагрузки страницы. Работает без правок ядра — только через штатные хуки `tools` и `ajax`.


---

## 📑 Оглавление

- [1. Общие сведения](#1-общие-сведения)
  - [1.1. Что такое Template Editor](#11-что-такое-template-editor)
  - [1.2. Ключевые возможности](#12-ключевые-возможности)
  - [1.3. Системные требования](#13-системные-требования)
  - [1.4. Совместимость](#14-совместимость)
  - [1.5. Ссылки](#15-ссылки)
- [2. Принципы работы](#2-принципы-работы)
  - [2.1. Архитектура](#21-архитектура)
  - [2.2. Структура плагина](#22-структура-плагина)
  - [2.3. Хуки и их роль](#23-хуки-и-их-роль)
  - [2.4. Как работает список файлов](#24-как-работает-список-файлов)
  - [2.5. Как работает редактор](#25-как-работает-редактор)
  - [2.6. Параметры URL](#26-параметры-url)
  - [2.7. Безопасность](#27-безопасность)
- [3. Пошаговая установка](#3-пошаговая-установка)
  - [3.1. Скачивание](#31-скачивание)
  - [3.2. Установка через FTP/SFTP](#32-установка-через-ftpsftp)
  - [3.3. Активация в админке](#33-активация-в-админке)
  - [3.4. Настройка прав](#34-настройка-прав)
  - [3.5. Проверка работоспособности](#35-проверка-работоспособности)
- [4. Пошаговое руководство по использованию](#4-пошаговое-руководство-по-использованию)
  - [4.1. Список файлов](#41-список-файлов)
  - [4.2. Открытие файла в редакторе](#42-открытие-файла-в-редакторе)
  - [4.3. Сохранение изменений](#43-сохранение-изменений)
  - [4.4. Скачивание резервной копии](#44-скачивание-резервной-копии)
  - [4.5. Горячие клавиши](#45-горячие-клавиши)
- [5. Конфигурация плагина](#5-конфигурация-плагина)
- [6. Пошаговое руководство по безопасности](#6-пошаговое-руководство-по-безопасности)
- [7. Возможные проблемы и их решения](#7-возможные-проблемы-и-их-решения)
- [8. Логи и диагностика](#8-логи-и-диагностика)
- [9. FAQ — часто задаваемые вопросы](#9-faq--часто-задаваемые-вопросы)
- [10. Обновление плагина](#10-обновление-плагина)
- [11. Удаление плагина](#11-удаление-плагина)
- [12. Дорожная карта](#12-дорожная-карта)
- [13. История изменений](#13-история-изменений)
- [14. Участие в разработке](#14-участие-в-разработке)
- [15. Лицензия](#15-лицензия)
- [16. Контакты и поддержка](#16-контакты-и-поддержка)

---

## 1. Общие сведения

### 1.1. Что такое Template Editor

**Template Editor** — это плагин для [Cotonti CMF](https://github.com/Cotonti/Cotonti), который даёт администратору возможность просматривать и редактировать любые `.tpl`-файлы прямо из админ-панели, без FTP/SFTP и без открытия файлов на сервере через ssh:

- **редактор CodeMirror 5** — подсветка HTML/JS/CSS, нумерация строк, парные скобки;
- **список файлов** — все `.tpl` из темы, админ-темы, плагинов, модулей и ядра в одном месте;
- **сохранение через AJAX** — без перезагрузки страницы, с индикатором статуса;
- **скачивание копии** — текущая версия любого файла в один клик.

Плагин устанавливается как обычное расширение Cotonti и **не требует правок ядра**. Вся логика работает через два хука: `tools` и `ajax`.

### 1.2. Ключевые возможности

- **Единый список файлов** — все `.tpl`, доступные для редактирования, с метками корней («Theme», «Plugin: foo», «Core admin»).
- **Редактор CodeMirror** — тема `eclipse`, режим `htmlmixed`, line numbers, match/autoClose brackets, `indentUnit=4`.
- **Резиновая обёртка** — контейнер редактора растягивается вертикально (`resize: vertical`), ResizeObserver обновляет CodeMirror при изменении размера.
- **Сохранение через fetch** — POST на `index.php?r=tpleditor` с `action=save`, `file`, `content`, `x` (CSRF).
- **Скачивание** — GET-ссылка на `action=download` с CSRF-токеном через `x`.
- **Защита от потери маркеров** — если в исходнике был `<!-- BEGIN: -->` и в новом содержимом его нет — сохранение отклоняется.
- **Очистка кеша Cotonti** — после успешного сохранения вызывается `Cot::$cache->clear()`.
- **Мультиязычность** — файлы `ru` и `en`.
- **Никаких правок ядра** — только хуки `tools` и `ajax`.

### 1.3. Системные требования

| Компонент | Минимальная версия | Рекомендуемая |
|-----------|--------------------|---------------|
| PHP       | 8.0                | 8.5+          |
| MySQL     | 8.0                | 8.4+          |
| MariaDB   | 10.2               | 10.6+         |
| Cotonti   | 1.0.x              | 1.0.x         |

**Расширения PHP:**
- `pdo_mysql` — обязательно;
- `mbstring` — для работы с UTF-8 и кириллицей;
- `json` — встроен в PHP ≥ 8.0.

**Права на файлы:**
- каталоги тем/плагинов/модулей и файлы `.tpl` должны быть доступны на чтение пользователю веб-сервера;
- для сохранения — на запись (`0664` на файлы, `0775` на каталоги; значения по умолчанию Cotonti).

**Клиент (браузер):**
- любой современный браузер с поддержкой `fetch`, `URLSearchParams`, `ResizeObserver` (Chrome/Edge ≥ 64, Firefox ≥ 69, Safari ≥ 13.1).

### 1.4. Совместимость

Плагин протестирован на:

- **Cotonti** 1.0.x;
- **MySQL** 8.4;
- **MariaDB** 10.2 — 10.11;
- **PHP** 8.5;
- **Apache** 2.4, **Nginx** (с php-fpm), **LiteSpeed**.

### 1.5. Ссылки

- **Маркетплейс Cotonti:** [abuyfile.com/market/cotonti/plugs/tpleditor](https://abuyfile.com/market/cotonti/plugs/tpleditor)
- **Репозиторий на GitHub:** [github.com/webitproff/tpleditor-cotonti](https://github.com/webitproff/tpleditor-cotonti)
- **Форум поддержки:** [abuyfile.com/forums/cotonti/custom/](https://abuyfile.com/forums/cotonti/custom/)
- **CodeMirror:** [codemirror.net/5/](https://codemirror.net/5/)

---

## 2. Принципы работы

### 2.1. Архитектура

Плагин состоит из двух файлов-обработчиков, одного файла с функциями и двух шаблонов. Никаких изменений схемы БД, кроме записей в `cot_core`, `cot_auth`, `cot_config` и `cot_plugins` (стандартный набор при установке).

Поток работы:

1. Пользователь открывает `/admin.php?m=other&p=tpleditor`.
2. Router (`system/router/Router.php::routeAdminOther`) подхватывает `p=tpleditor` и находит файл `tpleditor.tools.php` через хук `tools`.
3. **Без параметра `file`** — плагин собирает список всех `.tpl` (обход `themes/`, `plugins/`, `modules/`, `system/admin/tpl`) и рендерит `tpleditor.tools.tpl`.
4. **С параметром `file=<rel>`** — валидирует путь через `tpleditor_validate_path()`, читает содержимое и рендерит `tpleditor.editor.tpl` с CodeMirror.
5. **Сохранение** — fetch POST на `index.php?r=tpleditor` → `tpleditor.ajax.php` с `action=save`.
6. **Скачивание** — GET на тот же AJAX endpoint с `action=download`.

### 2.2. Структура плагина

```
plugins/tpleditor/
├── tpleditor.setup.php              # метаданные плагина + конфиг
├── tpleditor.tools.php              # хук tools: админ-страница
├── tpleditor.ajax.php               # хук ajax: save / read / download
├── inc/
│   └── tpleditor.functions.php      # бизнес-логика (API)
├── tpl/
│   ├── tpleditor.tools.tpl          # список файлов
│   └── tpleditor.editor.tpl         # редактор CodeMirror
└── lang/
    ├── tpleditor.ru.lang.php
    └── tpleditor.en.lang.php
```

### 2.3. Хуки и их роль

| Хук | Точка входа | Что делает |
|-----|-------------|------------|
| `tools` | `admin.php?m=other&p=tpleditor` | Режимы: список файлов / редактор CodeMirror |
| `ajax`  | `index.php?r=tpleditor` | Действия: `save`, `read`, `download` |

**Важно:** хуки объявляются **в самих файлах-обработчиках** через блок `[BEGIN_COT_EXT]`. Setup-файл содержит только метаданные (`Code`, `Name`, `Version`, `Auth_*`, `Lock_*`) и блок конфигурации `[BEGIN_COT_EXT_CONFIG]` с четырьмя настройками `edit_*`.

### 2.4. Как работает список файлов

1. Пользователь открывает `/admin.php?m=other&p=tpleditor` без параметра `file`.
2. `tpleditor_roots()` собирает список разрешённых корней — зависит от конфига:
   - `edit_themes` → `themes/<theme>/` (текущая тема пользователя);
   - `edit_admin` → `themes/admin/<admintheme>/` (если `admintheme` непустой);
   - `edit_plugins` → `plugins/<code>/tpl/` для каждого плагина с подкаталогом `tpl/`;
   - `edit_modules` → `modules/<code>/tpl/` для каждого модуля с подкаталогом `tpl/`;
   - **всегда** → `system/admin/tpl/` (шаблоны ядра).
3. `tpleditor_list_files()` рекурсивно обходит каждый корень, собирает `.tpl`, сортирует по `rel` через `strcasecmp`.
4. Каждая строка списка содержит: `rel` (путь), `size` (байты), `mtime` (дата), `label` (метка корня).
5. Ссылка `<a href="{FILE_URL}">` ведёт на тот же URL с `?file=<rel>`.

### 2.5. Как работает редактор

1. Пользователь кликает по файлу → `/admin.php?m=other&p=tpleditor&file=<rel>`.
2. `tpleditor_validate_path($file)` возвращает либо `null` (ошибка), либо абсолютный путь `realpath`.
3. Содержимое файла читается через `file_get_contents()` и передаётся в тег `{FILE_CONTENT}` (после `htmlspecialchars`).
4. Шаблон `tpleditor.editor.tpl` создаёт `<textarea id="tpleditor-source">` и инициализирует на нём `CodeMirror.fromTextArea`.
5. Кнопка «Сохранить» вызывает `save()` — fetch POST на AJAX endpoint с `x` (CSRF), `a=save`, `file`, `content=editor.getValue()`.
6. Ответ JSON: `{success: bool, message: string, bytes?: int}`.
7. Индикатор статуса справа от кнопок показывает «Сохранено (N B)» или «Ошибка: ...».

### 2.6. Параметры URL

| Параметр | Значение | Описание |
|----------|----------|----------|
| `m`      | `other`  | раздел «Прочее» в админке |
| `p`      | `tpleditor` | код плагина |
| `file`   | строка   | относительный путь к `.tpl` (например, `themes/mytheme/header.tpl`) |

AJAX-запросы:

| Параметр | Значение | Описание |
|----------|----------|----------|
| `r`      | `tpleditor` | код плагина для router |
| `a`      | `save` \| `read` \| `download` | действие |
| `file`   | строка   | относительный путь к `.tpl` |
| `content`| строка   | содержимое (только для `save`, POST) |
| `x`      | строка   | CSRF-токен `Cot::$sys['xk']` |

### 2.7. Безопасность

- **Двойной барьер авторизации:**
  - `system/admin/admin.other.php` до include проверяет `cot_auth('plug','tpleditor','A')` и редиректит на 403;
  - внутри `tpleditor.tools.php` и `tpleditor.ajax.php` — `cot_auth('admin','a','A') || cot_auth('plug','tpleditor','A')`.
  Третий аргумент `'A'` обязателен: при маске длиннее 1 символа `cot_auth()` возвращает массив `[bool, bool, bool]`, который в PHP всегда truthy. Явная маска `'A'` даёт скаляр `bool`.
- **CSRF:**
  - `cot_check_xg(false)` для GET (`download`);
  - `cot_check_xp()` для POST (`save`, `read`).
- **Валидация пути `tpleditor_validate_path()`:**
  - файл обязан оканчиваться на `.tpl`;
  - нет `\0`, пустых сегментов, `.` и `..`;
  - префикс пути должен совпадать с одним из разрешённых корней;
  - `realpath(file)` и `realpath(root)` должны лежать внутри `baseDir` — защита от симлинк-побега.
- **`htmlspecialchars`** — применяется к `FILE_REL` и `FILE_CONTENT` перед вставкой в HTML.
- **Проверка маркеров** — при сохранении, если в исходнике был `<!-- BEGIN: -->` и в новом содержимом его нет — запись отклоняется.
- **`Content-Type: application/octet-stream` + `X-Content-Type-Options: nosniff`** — для скачивания, браузер не пытается отрисовать `.tpl` как HTML.

---

## 3. Пошаговая установка

### 3.1. Скачивание

**Скачать** архив с [GitHub](https://github.com/webitproff/tpleditor-cotonti) или из [маркетплейса](https://abuyfile.com/market/cotonti/plugs/tpleditor).

### 3.2. Установка через FTP/SFTP

1. Сделать бекап сайта и базы данных.
2. Распаковать локально скачанный архив.
3. Загрузить папку `tpleditor/` в `/plugins/` вашего Cotonti-сайта.
4. Убедиться, что структура такая:

   ```
   /plugins/tpleditor/tpleditor.setup.php
   /plugins/tpleditor/tpleditor.tools.php
   /plugins/tpleditor/tpleditor.ajax.php
   /plugins/tpleditor/inc/tpleditor.functions.php
   /plugins/tpleditor/tpl/tpleditor.tools.tpl
   /plugins/tpleditor/tpl/tpleditor.editor.tpl
   /plugins/tpleditor/lang/tpleditor.ru.lang.php
   /plugins/tpleditor/lang/tpleditor.en.lang.php
   ```

### 3.3. Активация в админке

1. Войти в админку Cotonti.
2. Перейти в **Расширения → Управление плагинами**.
3. Найти **Template Editor** (`tpleditor`) в списке.
4. Нажать **«Установить»**.

После установки плагин появится в разделе **Инструменты** (`admin.php?m=other`) под названием «Редактор шаблонов».

### 3.4. Настройка прав

По умолчанию (значения `Auth_members=''`, `Lock_members='RW12345A'` в setup-файле) доступ к плагину имеет **только суперадмин** (`COT_GROUP_SUPERADMINS`, id=5). Групповые права через `admin.php?m=rights` заблокированы — `Lock=255` запирает все биты.

Чтобы расширить доступ:

**Через SQL** (единственный способ при `Lock=255`):

```sql
-- Выдать группе «Администраторы» право A на tpleditor
UPDATE cot_auth
SET auth_rights = 128
WHERE auth_code = 'plug' AND auth_option = 'tpleditor' AND auth_groupid = 6;
```

Либо **до установки плагина** исправить в `tpleditor.setup.php`:

```ini
Auth_members=RWA
Lock_members=
```

и переустановить плагин.

### 3.5. Проверка работоспособности

1. Открыть **Инструменты → Редактор шаблонов** (`admin.php?m=other&p=tpleditor`).
2. Проверить, что отображается список `.tpl`-файлов с метками «Theme», «Admin theme», «Plugin: ...», «Core admin».
3. Кликнуть на любой файл темы → откроется редактор CodeMirror.
4. Изменить пару символов в комментарии, нажать **«Сохранить»** — статус должен показать «Сохранено (N B)».
5. Нажать **«Скачать резервную копию»** — браузер скачает текущую версию файла.

---

## 4. Пошаговое руководство по использованию

### 4.1. Список файлов

При открытии `admin.php?m=other&p=tpleditor` вы видите:

- заголовок «Редактор шаблонов»;
- список `.tpl`-файлов, отсортированный по пути;
- каждая строка: иконка, `<code>` с путём, бейдж-метка корня, размер в байтах и дата изменения справа.

Кликабельна вся строка — ведёт в редактор.

### 4.2. Открытие файла в редакторе

При клике на файл открывается страница редактора:

- заголовок «Редактирование» + путь к файлу;
- панель кнопок: **«К списку»**, **«Сохранить»**, **«Скачать резервную копию»**, индикатор статуса;
- сама область CodeMirror с подсветкой и нумерацией строк.

Контейнер редактора можно тянуть за нижний край (`resize: vertical`) — высота меняется от 200px до 90vh.

### 4.3. Сохранение изменений

1. Отредактируйте содержимое в CodeMirror.
2. Нажмите **«Сохранить»** или `Ctrl+S` / `Cmd+S`.
3. Индикатор справа от кнопок покажет «...» → «Сохранено (N B)» или «Ошибка: <текст>».

**Что происходит на сервере:**
- проверка CSRF (`cot_check_xp`);
- валидация пути (`tpleditor_validate_path`);
- проверка маркеров (`<!-- BEGIN: -->` в исходнике → `<!-- BEGIN: -->` в новом содержимом);
- `file_put_contents`;
- `Cot::$cache->clear()`.

При отклонении — файл на диске не меняется, ошибка отображается в индикаторе.

### 4.4. Скачивание резервной копии

Кнопка **«Скачать резервную копию»** открывает в новой вкладке GET-ссылку на AJAX endpoint с `action=download`. Сервер отдаёт файл с заголовками:

- `Content-Type: application/octet-stream`
- `Content-Disposition: attachment; filename="<basename>"`
- `Content-Length: <size>`
- `X-Content-Type-Options: nosniff`

**Важно:** это скачивание текущего состояния файла, а не бэкап из истории. Плагин не создаёт `.bak`-файлов — бекап делайте сами перед редактированием.

### 4.5. Горячие клавиши

| Комбинация | Действие |
|-----------|----------|
| `Ctrl+S` / `Cmd+S` | Сохранить файл |
| `Tab` | Увеличить отступ (indent) |
| `Shift+Tab` | Уменьшить отступ |

Стандартное действие браузера «Сохранить страницу» на `Ctrl+S` подавляется.

---

## 5. Конфигурация плагина

Настройки доступны в **Расширения → Управление плагинами → Template Editor → Настроить** (`admin.php?m=config&n=edit&o=plug&p=tpleditor`):

| Опция | Тип | По умолчанию | Описание |
|-------|-----|--------------|----------|
| `edit_themes`  | radio (0/1) | 1 | Разрешить редактирование `.tpl` темы текущего пользователя |
| `edit_admin`   | radio (0/1) | 1 | Разрешить редактирование `.tpl` админ-темы |
| `edit_plugins` | radio (0/1) | 1 | Разрешить редактирование `.tpl` плагинов |
| `edit_modules` | radio (0/1) | 1 | Разрешить редактирование `.tpl` модулей |

Ядровой корень `system/admin/tpl/` включается **всегда**, независимо от настроек.

Изменения применяются немедленно: `tpleditor_flag()` читает `Cot::$cfg['plugin']['tpleditor'][<name>]` при каждом вызове `tpleditor_roots()`.

---

## 6. Пошаговое руководство по безопасности

Плагин даёт **прямой доступ к файлам сервера**, поэтому требует дисциплины:

1. **Не давайте право `A` на tpleditor кому попало.** Через плагин можно испортить шаблон сайта до нерабочего состояния. Право `A` — только доверенным администраторам.
2. **Регулярно делайте бекапы.** Плагин **не создаёт бэкапов** — если сохранить испорченный файл, откатить можно только из внешнего источника (git, ручной архив, скачанная ранее копия через «Скачать»).
3. **Используйте HTTPS.** Содержимое `.tpl` и CSRF-токен передаются в POST; без HTTPS они видны в открытом виде.
4. **Проверяйте `Lock_members` / `Lock_guests` в setup-файле.** По умолчанию они `RW12345A` (полная блокировка), что не даёт менять права через UI. Расширяйте осознанно.
5. **Не отключайте проверку CSRF.** `cot_check_xg` / `cot_check_xp` в `tpleditor.ajax.php` — единственный барьер против CSRF-атак через сторонние сайты.
6. **Проверяйте `Content-Type: application/octet-stream`** на скачивании. Не убирайте заголовок `nosniff` — браузер иначе может попытаться отрисовать `.tpl` как HTML и выполнить встроенный JS.
7. **Ограничьте доступ к `/plugins/` через веб-сервер.** `.tpl`-файлы не должны быть доступны напрямую по HTTP (только через плагин). Проверьте `deny from all` / `location ~ \.tpl$ { deny all; }`.
8. **Проверяйте маркеры шаблонов перед сохранением.** Встроенная защита от потери `<!-- BEGIN: -->` спасает от случайного сохранения пустого файла, но не от сознательной порчи.

---

## 7. Возможные проблемы и их решения

### 7.1. Плагин не появляется в «Инструментах»

**Причина:** плагин установлен, но хук `tools` не зарегистрирован.

**Решение:**
- Проверить в **Расширения → Управление плагинами** — статус должен быть «Активен».
- Проверить `cot_plugins` в БД: `SELECT * FROM cot_plugins WHERE pl_code='tpleditor'`. Должно быть минимум две записи — `pl_part='tools'`, `pl_part='ajax'`.
- Если записей нет — **Обновить** плагин через админку (`Extensions → tpleditor → Обновить`).

### 7.2. Ошибка 403 Forbidden при открытии

**Причина:** у пользователя нет права `A` на `plug/tpleditor` или `admin/a`.

**Решение:**
- Проверить группу пользователя: `SELECT * FROM cot_auth WHERE auth_code='plug' AND auth_option='tpleditor' AND auth_groupid=<id>`.
- При `auth_rights < 128` — выдать право `A` через SQL (`UPDATE` до 128) или переустановить плагин после правки setup-файла.

### 7.3. Редактор не загружается (пустой `<textarea>`)

**Причина:** CodeMirror не подключился (CDN недоступен, CSP блокирует `cdn.jsdelivr.net`).

**Решение:**
- Открыть консоль браузера — будут ошибки 404/CSP на запросы к jsdelivr.
- Скачать CodeMirror 5.65.16 локально и заменить ссылки в `tpleditor.editor.tpl` на локальные.
- Или разрешить `cdn.jsdelivr.net` в CSP сайта.

### 7.4. Сохранение возвращает «Invalid path»

**Причина:** `tpleditor_validate_path()` отверг путь. Возможные причины:

- Файл не оканчивается на `.tpl`.
- Путь содержит `..`, пустые сегменты, `\0`.
- Файл — симлинк, указывающий за пределы `baseDir` или за пределы разрешённого корня.
- Каталог из `tpleditor_roots()` не существует (тема пользователя удалена, плагин отключён).

**Решение:** проверить `realpath` файла (`realpath /path/to/file` в ssh) и убедиться, что он начинается с `/полный/путь/к/baseDir/themes/<theme>/` или аналогичного.

### 7.5. Сохранение возвращает «Content rejected: template markers missing»

**Причина:** защита от потери маркеров. В исходнике был `<!-- BEGIN: ... -->`, а в новом содержимом его нет.

**Решение:**
- Проверить, что вы не удалили все BEGIN-маркеры из `.tpl`.
- Если файл — не Cotonti-шаблон (например, `.tpl` для чего-то другого) — сохранить его через FTP или предварительно удалить проверку в `tpleditor.ajax.php` (не рекомендуется).

### 7.6. «File not writable»

**Причина:** у веб-сервера нет прав на запись.

**Решение:**
- Проверить права: `ls -la /path/to/file.tpl` — должно быть `rw-` для пользователя, под которым работает PHP-FPM.
- Проверить владельца: `chown www-data:www-data /path/to/file.tpl` (или того пользователя, под которым работает PHP).
- Проверить, что каталог тоже доступен на запись (для `file_put_contents` нужен доступ к каталогу).

### 7.7. Скачивание открывает файл в браузере, а не скачивает

**Причина:** браузер игнорирует `Content-Disposition: attachment` или MIME-sniffing.

**Решение:**
- Проверить, что заголовок `X-Content-Type-Options: nosniff` присутствует (в `tpleditor.ajax.php`, action=download).
- Проверить, что `Content-Type: application/octet-stream` (а не `text/html`).

### 7.8. Список файлов пуст

**Причина:** все `edit_*` выключены, либо каталоги не существуют.

**Решение:**
- Проверить настройки плагина — все четыре флага должны быть `Да`.
- Проверить, что у вас есть хотя бы одна тема (`themes/<theme>/`), и что `Cot::$usr['theme']` — существующий каталог.
- Ядровой корень (`system/admin/tpl/`) присутствует всегда — если и его нет в списке, что-то серьёзнее (плагин не установлен корректно).

---

## 8. Логи и диагностика

### 8.1. Где смотреть

| Что | Где |
|-----|-----|
| Ошибки PHP | `/var/log/php/error.log`, `error_log` сайта |
| Ошибки MySQL | `/var/log/mysql/error.log` |
| Cotonti debug | `datas/config.php` → `$cfg['debug_mode'] = true` |
| Ответы AJAX | DevTools → Network → фильтр по `r=tpleditor` |

### 8.2. Включение debug

В `datas/config.php`:

```php
$cfg['debug_mode'] = true;
$cfg['devmode']    = true;
```

В админке откроется панель с SQL-запросами, включая запросы плагина.

**Внимание:** не оставляйте debug на production.

### 8.3. Ответы AJAX

Откройте DevTools → Network, фильтр `tpleditor`. При сохранении увидите POST-запрос на `index.php?r=tpleditor`. Ответ — JSON:

```json
{
  "success": true,
  "message": "Сохранено",
  "bytes": 1234
}
```

При ошибке:

```json
{
  "success": false,
  "message": "Invalid CSRF token"
}
```

Код HTTP будет 200 (для `catch`) или 400/403 (для явных отказов). С версии 1.0.0 — см. историю изменений.

---

## 9. FAQ — часто задаваемые вопросы

**Q: Плагин работает без интернета?**
A: Только если CodeMirror развёрнут локально. По умолчанию ресурсы подключаются с `cdn.jsdelivr.net`, при отсутствии интернета редактор не инициализируется.

**Q: Создаёт ли плагин `.bak`-файлы?**
A: Нет. С версии 1.0.0 создание `.bak.YmdHis`-файлов **отключено**. Текущее состояние файла можно скачать кнопкой «Скачать резервную копию» — но это не история, а снимок на момент скачивания.

**Q: Можно ли откатить изменения?**
A: Только вручную: скачанная ранее копия, git-история, резервная копия сайта. Плагин не хранит историю.

**Q: Работает ли на Cotonti Siena 0.9.x?**
A: Нет. Плагин использует `Cot::$cfg`, `Cot::$sys`, `Cot::$usr` — API Cotonti 1.0.x. Для 0.9.x потребуется переписать.

**Q: Поддерживает ли плагин редактирование `.php`?**
A: Нет. `tpleditor_validate_path()` требует расширение `.tpl` — PHP-файлы плагин редактировать не даёт.

**Q: Что будет при удалении плагина?**
A: Записи в `cot_core`, `cot_auth`, `cot_config`, `cot_plugins` удалятся. Файлы плагина останутся на диске до ручного удаления. Все `.tpl`, отредактированные плагином, останутся в изменённом виде.

**Q: Можно ли использовать на нескольких сайтах?**
A: Да, плагин не привязан к домену.

**Q: Почему при установке только суперадмин имеет доступ?**
A: `Auth_members=''`, `Lock_members='RW12345A'` — это осознанный fail-safe по умолчанию. Администраторы должны явно выдать права через SQL или переустановку с изменённым setup-файлом (см. раздел 3.4).

**Q: Зачем нужен `read` action, если в шаблонах его нет?**
A: Резервный. В текущих шаблонах плагина action `read` не вызывается — содержимое приходит через `{FILE_CONTENT}` при серверном рендере. Но эндпоинт готов для внешних интеграций.

**Q: Что за проверка `<!-- BEGIN: -->` в save?**
A: Защита от случайного сохранения пустого/обрезанного файла. Если в исходнике был хоть один Cotonti-маркер `<!-- BEGIN: -->`, а в новом содержимом ни одного — сервер отказывается записывать.

---

## 10. Обновление плагина

1. **Сделать бэкап** файлов плагина и (на всякий случай) базы данных.
2. Скачать новую версию из маркетплейса или с GitHub.
3. В админке Cotonti → **Расширения → Управление плагинами → Template Editor → Обновить**.
4. Проверить, что хуки `tools` и `ajax` остались активными.
5. Открыть `admin.php?m=other&p=tpleditor` и проверить работоспособность.

**Важно:** если версия плагина в setup-файле совпадает с установленной, Cotonti вернёт `COT_EXT_NOTHING_TO_UPDATE` и обновление не выполнится. Для принудительного обновления — **Обновить** с `force_update=true` (через `Extensions → details`), либо изменить `Version` в setup-файле.

---

## 11. Удаление плагина

1. В админке Cotonti → **Расширения → Управление плагинами → Template Editor → Удалить**.
2. Удалить папку `plugins/tpleditor/` вручную, если она осталась.
3. Очистить кэш Cotonti.

**Важно:** плагин не хранит собственных данных в БД. Удаление безопасно. Все отредактированные через плагин `.tpl` остаются в изменённом виде — если хотите откатить, используйте резервные копии сайта.

---

## 12. Дорожная карта

- [ ] **История сохранений** — создание `.bak`-файлов с ограничением по количеству (например, 5 последних).
- [ ] **Сравнение с исходником** — diff между текущим и скачанным состоянием.
- [ ] **Поиск по содержимому** — grep по всем `.tpl` из списка.
- [ ] **Создание / удаление** `.tpl`-файлов из UI.
- [ ] **Редактирование `*.tpl` в подкаталогах плагинов** (сейчас только `tpl/`).
- [ ] **Шаблоны ядра** — включить `modules/<code>/tpl/` как опциональный корень.
- [ ] **Поддержка Pug/Twig** — расширение списка поддерживаемых расширений.
- [ ] **Тёмная тема CodeMirror** — переключатель `eclipse` / `dracula`.
- [ ] **Автосохранение** — по таймеру с индикатором.
- [ ] **REST API** — редактирование через API без UI.

---

## 13. История изменений

### v1.0.0 (2026-11-01)

- Первая публичная версия.
- Хуки `tools` (админ-страница) и `ajax` (save/read/download).
- CodeMirror 5.65.16 с темой `eclipse` и режимом `htmlmixed`.
- Список всех `.tpl` в теме, админ-теме, плагинах, модулях и ядре.
- Валидация пути с защитой от симлинк-побега.
- CSRF: `cot_check_xg` (GET) / `cot_check_xp` (POST).
- Проверка маркеров `<!-- BEGIN: -->` при сохранении.
- Очистка кеша Cotonti после save.
- Локализация `ru` / `en`.
- Конфиг: `edit_themes`, `edit_admin`, `edit_plugins`, `edit_modules`.
- Создание `.bak.YmdHis`-файлов **отключено** — бэкап только через кнопку «Скачать резервную копию».

---

## 14. Участие в разработке

Pull-request'ы приветствуются. Перед отправкой:

1. Форкните [репозиторий](https://github.com/webitproff/tpleditor-cotonti).
2. Создайте ветку `feature/your-feature` или `fix/your-bug`.
3. Следуйте PSR-12.
4. Все публичные функции — с префиксом `tpleditor_`.
5. Все SQL-параметры — через `Cot::$db->prep()` или биндинг.
6. Все пользовательские данные в HTML — через `htmlspecialchars()`.
7. Мультиязычность — через `$L['tpleditor_*']`.
8. Обновите `README.md` и `CHANGELOG`.

---

## 15. Лицензия

BSD 3-Clause. Полный текст — в файле `License.txt`.

---

## 16. Контакты и поддержка

- **Маркетплейс Cotonti:** [abuyfile.com/market/cotonti/plugs/tpleditor](https://abuyfile.com/market/cotonti/plugs/tpleditor)
- **Репозиторий GitHub:** [github.com/webitproff/tpleditor-cotonti](https://github.com/webitproff/tpleditor-cotonti)
- **Форум поддержки:** [abuyfile.com/forums/cotonti/custom/](https://abuyfile.com/forums/cotonti/custom/)
- **Автор:** [webitproff](https://github.com/webitproff)
- **Issues:** [github.com/webitproff/tpleditor-cotonti/issues](https://github.com/webitproff/tpleditor-cotonti/issues)

# WST2 · Activity 3 – Access Control Bug Hunt

**Name:** Rey Anthony Dahunog · **Section:** 4C

This builds on Activity 2 (role-based dashboard). A forum page, `posts.php`, lets users delete posts through `delete_post.php`. The original `delete_post.php` was vulnerable. I found the security problems in it and rewrote the file securely.

The original vulnerable file is kept as `delete_post_ORIGINAL.php.txt` for reference. It is a `.txt` file so it cannot run.

## Problems found and fixed
| # | Problem | Risk | Fix |
|---|---|---|---|
| 1 | Missing authentication | A logged-out visitor can delete posts | `require_login()` from `auth.php` |
| 2 | Broken access control (IDOR) | Change `?id=` to delete other users' posts | `DELETE ... WHERE id = ? AND user_id = ?`, admin may moderate |
| 3 | SQL injection | `?id=1 OR 1=1` deletes every post | Prepared statement + `FILTER_VALIDATE_INT` |
| 4 | Delete by GET link / CSRF | An `<img>` on another site deletes a victim's post | POST only (405 otherwise) + CSRF token checked with `hash_equals()` |
| 5 | No accounting | No record of who deleted what | Every success and denied attempt goes into `audit_logs` |
| 6 | No input validation, no `exit` after `header()` | Warnings leak paths, false success, code keeps running | Validate id (400), check `affected_rows`, `exit` after redirect |

## Files changed
- `delete_post.php` – the secure rewrite.
- `posts.php` – Delete is now a POST form with a hidden CSRF token, shown only to the owner or an admin.
- `includes/helpers.php` – `csrf_token()`, which creates the token once per session with `bin2hex(random_bytes(32))`.
- `database/03_posts_table.sql` – creates the `posts` and `audit_logs` tables with sample posts.

## How to run
1. Copy this folder to `htdocs/wst2_auth` (XAMPP).
2. In phpMyAdmin, run `database/01_users_table.sql`, `02_grades_table.sql`, then `03_posts_table.sql`. You need at least 2 student accounts first. Re-run `03` any time to reset the posts.
3. Log in and open **Forum** (`posts.php`).

> Test only on your own localhost and sandbox data (RA 10175).

## Write-up
The one-page write-up is [DAHUNOG_4C_A3.pdf](DAHUNOG_4C_A3.pdf) (editable: `DAHUNOG_4C_A3.docx`).

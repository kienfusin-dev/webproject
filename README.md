# ClassTrack — Classroom Availability Management Website

A beginner-friendly PHP + MySQL web app for managing and viewing
**classroom** availability in a school, built to run on **XAMPP** and
edited in **VS Code**.

## Features
- User registration & login **with a username (not email/Gmail)** —
  passwords hashed with `password_hash`
- Session-based authentication
- **Horizontal top navigation**: Dashboard, Classrooms, Schedule,
  Profile, Logout (collapses into a mobile menu on small screens)
- **Every new instructor is automatically assigned 4 default
  subjects** the moment they register, and lands on a **"Back to
  Login" button** right after registering
- Dashboard with live classroom-availability stats + "Today's Classes"
- Classrooms page with green/red status cards, a filter bar, and a
  **working "Reserve Room" / "Release Room" button**
- Schedule page — instructor's assigned subjects + weekly class
  timetable, grouped by day
- **Profile page** with a dark profile panel (avatar, instructor ID,
  department, username, contact number) and the instructor's
  assigned subjects listed at the bottom of that panel
- Clean, responsive, mobile-friendly design

## Project Structure
```
classroom-availability/
├── config/
│   └── db.php                      # Database connection settings
├── includes/
│   ├── header.php                  # Shared <head> + opening <body>
│   ├── navbar.php                   # Horizontal top nav (Dashboard/Classrooms/Schedule/Profile/Logout)
│   ├── footer.php                   # Shared footer + closing tags
│   └── auth_check.php                # Redirects to login.php if not logged in
├── assets/
│   ├── css/style.css                 # All styling
│   └── js/script.js                  # Mobile navbar toggle
├── database/
│   └── classroom_availability.sql    # Schema + sample classrooms/subjects/schedule
├── index.php                        # Entry point (redirects to login/dashboard)
├── register.php                     # Registration + auto-assigns 4 default subjects
├── login.php
├── logout.php
├── dashboard.php
├── rooms.php                        # Classroom listing + Reserve/Release buttons
├── reserve_room.php                  # Handles the Reserve/Release form submissions
├── schedule.php                     # Instructor's subjects + weekly schedule
├── profile.php                      # Profile panel + assigned subjects + edit form
└── README.md
```

## Setup Instructions (XAMPP + VS Code)

### 1. Install XAMPP
Download and install XAMPP from https://www.apachefriends.org, then
start **Apache** and **MySQL** from the XAMPP Control Panel.

### 2. Copy the project into htdocs
Copy the entire `classroom-availability` folder into your XAMPP
`htdocs` directory:
- Windows: `C:\xampp\htdocs\classroom-availability`
- macOS: `/Applications/XAMPP/htdocs/classroom-availability`
- Linux: `/opt/lampp/htdocs/classroom-availability`

### 3. Create (or update) the database

**Fresh install:** open `http://localhost/phpmyadmin` → **Import** →
choose `database/classroom_availability.sql` → **Go**. This creates
`classroom_availability_db` with `users`, `classrooms`, `subjects`,
and `class_schedules`, plus sample data.

**Already had this project running before?** The `users` and
`classrooms` tables changed (new columns, and login now uses a
username instead of email), so re-importing the `.sql` file alone
won't update your existing tables — `CREATE TABLE IF NOT EXISTS`
skips tables that already exist. Run this in phpMyAdmin's **SQL**
tab instead:
```sql
ALTER TABLE users
    ADD COLUMN department VARCHAR(100) DEFAULT NULL,
    ADD COLUMN contact_number VARCHAR(30) DEFAULT NULL;

-- Renames the existing `email` column to `username` — whatever was
-- stored there (e.g. an email address) becomes the login username.
ALTER TABLE users CHANGE email username VARCHAR(50) NOT NULL;

ALTER TABLE classrooms
    ADD COLUMN occupied_by INT DEFAULT NULL;
```

### 4. Configure the database connection
Open `config/db.php`. The defaults match a stock XAMPP install:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'classroom_availability_db');
```
If you've set a MySQL root password in XAMPP, update `DB_PASS`
accordingly.

### 5. Run the site
With Apache running, open:
```
http://localhost/classroom-availability/
```
You'll be redirected to the login page. Click **Register here** to
create your instructor account — you'll automatically be assigned 4
default subjects — then log in.

> ⚠️ **About the pre-seeded demo schedule:** the sample subjects and
> weekly schedule in the SQL file are pre-assigned to
> `instructor_id = 1`. On a fresh database, the **first account you
> register** gets id `1`, so that account will see the demo schedule
> on the Schedule page in addition to its own 4 auto-assigned
> subjects. Every account after that only has its 4 auto-assigned
> subjects (no schedule times) until you add some.

### 6. Edit in VS Code
Open the `classroom-availability` folder in VS Code
(`File > Open Folder`). Recommended extension: **PHP Intelephense**.

## How It Works (for beginners)

- **Registration (`register.php`)** validates the form, hashes the
  password, inserts the new row into `users`, then loops through a
  `$DEFAULT_SUBJECTS` array (defined at the top of the file) and
  inserts one `subjects` row per item with `instructor_id` set to the
  new user's id. Edit that array to change what new instructors get.
- **Login (`login.php`)** verifies credentials and starts the session.
- **`includes/auth_check.php`** guards every protected page,
  redirecting to `login.php` if there's no session.
- **`includes/navbar.php`** is the shared top navigation, included
  right after `includes/header.php` on every protected page.
- **Classrooms (`rooms.php`)** lists every room with a `LEFT JOIN` to
  `users` so it can show who currently occupies an unavailable room.
  Each card's button depends on the room's state:
  - **Available** → green "Reserve Room" button (submits to
    `reserve_room.php` with `action=reserve`)
  - **Unavailable, reserved by you** → orange "Release Room" button
    (submits with `action=release`)
  - **Unavailable, reserved by someone else** → disabled "Not
    Available" button, with a note showing who has it
- **`reserve_room.php`** is a small POST-only handler: it re-checks
  the room's current status server-side before updating it (so two
  instructors can't both reserve the same room), sets
  `status = 'unavailable'` and `occupied_by = <you>` on reserve, or
  clears both fields on release — then redirects back to
  `rooms.php` with a success/error message in the URL.
- **Schedule (`schedule.php`)** lists the logged-in instructor's
  subjects, then a weekly timetable grouped by day (joins
  `class_schedules` → `subjects` → `classrooms`, filtered by
  `instructor_id`).
- **Dashboard (`dashboard.php`)** shows classroom stats plus
  "Today's Classes" — same kind of query as Schedule, filtered to
  `WHERE day_of_week = <today>`.
- **Profile (`profile.php`)** shows a dark profile panel (avatar,
  instructor ID, department, username, contact number, member-since
  date) with the instructor's assigned subjects listed at the
  bottom, plus an edit form on the right to update name, department,
  contact number, and password.
- All database queries use **prepared statements** to prevent SQL
  injection.

## Database Tables

**`users`**
| Column          | Description                                   |
|-----------------|------------------------------------------------|
| full_name       | Instructor's name                               |
| username        | Used to log in (no email/Gmail required)        |
| password        | Bcrypt hash                                     |
| department      | Editable on the Profile page                    |
| contact_number  | Editable on the Profile page                    |

**`classrooms`**
| Column        | Description                                      |
|---------------|----------------------------------------------------|
| status        | `available` or `unavailable`                       |
| occupied_by   | `users.id` of whoever reserved the room (or NULL)  |
| *(plus room_number, room_type, building, floor, capacity, equipment, description as before)* | |

**`subjects`** — subjects/courses an instructor teaches (subject_code,
subject_name, units, instructor_id).

**`class_schedules`** — one row per weekly class meeting (subject_id,
classroom_id, section, day_of_week, start_time, end_time).

> `subjects`/`class_schedules`/`occupied_by` use plain INT columns
> instead of formal FOREIGN KEY constraints, so sample data can be
> imported/inserted without worrying about insert order. Keep the
> ids consistent yourself when adding data by hand.

## Customizing Data
- To change what new instructors are assigned by default, edit the
  `$DEFAULT_SUBJECTS` array near the top of `register.php`.
- To add a subject/schedule for a specific existing instructor, look
  up their `id` in the `users` table via phpMyAdmin, then use it as
  `instructor_id` when inserting into `subjects`.
- To manually mark a room unavailable (e.g. for maintenance) without
  assigning it to an instructor, set `status = 'unavailable'` and
  leave `occupied_by` as `NULL` — the Classrooms page will show
  "Not Available" with no occupant note.

## Notes / Next Steps You Could Add
- An admin role to manage classrooms/subjects from the UI.
- Prevent double-booking by time — right now a reserved room stays
  unavailable until released, rather than being tied to a specific
  time slot.
- Let instructors reserve a room for a specific subject/date instead
  of an indefinite "in use" toggle.
- Optional email field for password-reset purposes (still logging
  in with username).

Enjoy, and feel free to modify the code — it's intentionally kept
simple and commented for learning purposes.

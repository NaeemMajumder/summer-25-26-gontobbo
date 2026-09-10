# GONTOBBO — Bus Management System (Passenger Module)

This is a rebuild of the **Passenger module only**, following the project's
Technical Documentation exactly: single front controller, one controller per
role, MySQLi with `mysqli_prepare` everywhere, session + cookie auth, CSRF on
every POST, and a vanilla HTML/CSS/JS frontend with AJAX (no frameworks).

Driver, Admin and Manager are a separate module and are **not** included here.

## Quick Start (XAMPP / local PHP + MySQL)

1. Copy this folder into your server root, e.g. `htdocs/summer-25-26-bus-management-system`.
2. Create the database by importing `database.sql` (phpMyAdmin, or):
   ```
   mysql -u root -p < database.sql
   ```
   This creates the `bus_management_system` database, the full schema, and
   demo seed data (buses, routes, stops, promo codes, a few upcoming and
   completed trips).
3. Check `config/config.php` if your MySQL user/password differ from the
   defaults (`root` / no password).
4. Visit `index.php` in your browser.

### Test account

| Email | Password |
|---|---|
| `passenger@test.com` | `password123` |

This account already has an upcoming booking (with a promo code and
wheelchair request applied — try Modify/Cancel/Pay on it), plus two completed
trips with reviews already left, so **My Tickets**, **My Reviews**, and
**Bus Ratings** all have real data the first time you log in. You can also
register a brand-new passenger account from `index.php?page=register`.

## What changed vs. the previous version

The previous code had already made a start, but diverged from the Technical
Documentation in a few important ways and had some real bugs. This rebuild
fixes all of it, passenger-side:

- **Architecture** — consolidated `booking_controller.php`, `profile_controller.php`,
  `review_controller.php`, `search_controller.php` and the standalone `ajax/`
  folder into the two files the documentation actually specifies:
  `controllers/passenger_controller.php` (one controller for the whole role)
  and `controllers/ajax_controller.php` (every JSON endpoint). `config/app.php`
  and `config/database.php` were merged into the single `config/config.php`
  the doc calls for.
- **Router** — `index.php` is now a real single front controller
  (`index.php?page=<page>&action=<action>&id=<id>`), and every form/link goes
  through it instead of posting straight to a controller file.
- **Security fixes**:
  - Every DB query now uses `mysqli_prepare` — several models previously
    built SQL with string concatenation.
  - `get_booking_by_id()` now filters by `user_id`, so one passenger can no
    longer view or edit another passenger's booking by guessing an ID.
  - Cancel/pay/review-delete etc. are POST + CSRF-token only; they were
    plain GET links before.
  - Session cookie is `httponly` + `samesite=Lax`; there's an idle session
    timeout (`SESSION_TIMEOUT`, 30 min); login regenerates the session ID.
  - Login/registration use the same generic error message either way, so a
    guess can't be used to enumerate which emails are registered.
- **Missing backend implemented from scratch** — `trip_model.php`,
  `promo_model.php`, and both AJAX endpoint files were empty (0 bytes) before.
  Search, trip details, fare calculation with promo codes, and seat inventory
  (atomic increment/decrement, race-safe) are all real now.
- **Database** — added the `reset_token` and `remember_token` columns the
  documentation's own schema calls for (needed for Forgot Password and
  Remember Me), and renamed the schema file to `database.sql` to match the
  documented filename.
- **Every Passenger page is now data-driven**: Trip Details/Booking, Confirm
  & Pay, and the Ticket page previously showed a hardcoded bus and a fixed
  ৳1200 fare; they now reflect the real trip, seats, boarding/dropping
  stops, and promo discount, with live AJAX fare recalculation.
- **All three unique features are implemented**: Bus Company Rating
  (aggregated from `reviews`, shown on Search/Trip Details and its own
  ranked Bus Ratings page), Wheelchair Special Request (on the booking
  form), and the COD/Counter Payment System (chosen at Confirm & Pay, with
  a self-serve "mark as paid" for COD).

## Folder structure

```
config/config.php          DB connection, session hardening, app constants
helpers/helpers.php        esc(), CSRF, session/auth guards, fare math, etc.
models/                    One file per entity, procedural, mysqli_prepare only
controllers/
  auth_controller.php      login / register / logout / forgot+reset password
  passenger_controller.php Every Passenger action (booking, profile, reviews, unique features)
  ajax_controller.php      search_trips, trip_details, route_stops, fare_calc
views/
  partials/                header.php, footer.php, navbar.php
  auth/                    login.php, register.php, forgot.php
  passenger/               dashboard, search, trip, confirm, ticket, modify,
                            my_tickets, profile, reviews, busratings, feedback
assets/                    style.css, app.js (vanilla JS, fetch-based AJAX)
database.sql               Schema + demo seed data
```

## Notes / intentional scope decisions

- Registration only ever creates a **Passenger** account in this build —
  Driver/Manager role selection is part of the other module and isn't wired
  up here.
- "Delete Account" permanently removes the user's bookings and reviews too
  (inside one DB transaction) rather than a soft-delete, since the schema
  has no status/soft-delete column for `users`.
- There's no mail server configured, so **Forgot Password** takes you
  straight to the "set a new password" step instead of emailing a link
  (clearly labelled as a demo shortcut in the UI).
- The ticket "code" is a formatted version of the booking ID
  (e.g. `GNT-000042`) rather than a generated image/QR, since the schema
  has no dedicated code column — this is what the Driver's future "Verify
  Passenger" screen would look up.

This build was tested end-to-end (registration, login, forgot/reset
password, search → trip details → confirm & pay → book, modify, cancel,
mark-as-paid, add/edit/delete review, profile update, change password,
delete account, and cross-account access checks) against a real MySQL
database with no PHP warnings, notices, or errors.

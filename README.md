# Simple Events Pro

Premium features for [Simple Events CPT](https://github.com/jlisojo/simple-events-cpt).

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Simple Events CPT installed and active

## Current features

- **Recurrence settings** for daily, weekly, and monthly events
- **Automatic next-occurrence calculation** integrated into existing event archives and shortcodes
- **Occurrence exceptions**: skip a specific recurring date or reschedule it to a different date
- **Daily WP-Cron synchronization** with stale-date refresh during public requests

Editors add exceptions through a simple metabox interface without affecting the base recurrence rule. Skipped occurrences are automatically passed over; rescheduled occurrences move to their new dates.

## Development status

Excluding or editing individual occurrences beyond exceptions is planned for a later release.

## License

GPL-2.0-or-later
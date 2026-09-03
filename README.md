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
- **Calendar view** with month navigation, event cards inline, and responsive design
- **Daily WP-Cron synchronization** with stale-date refresh during public requests

### Calendar shortcode

```
[simple_events_calendar]
[simple_events_calendar month="2026-02-15" show_filters="true"]
```

| Parameter | Default | Description |
|---|---|---|
| `month` | Current month | Initial month in Y-m-d format |
| `show_filters` | `true` | Show category filters |

The calendar displays events inline on their dates, with month navigation. Click event titles to view details.

## License

GPL-2.0-or-later
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
- **Gutenberg Event Calendar block** with live preview and sidebar settings
- **iCalendar export** for individual events and event feeds
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

### Gutenberg Event Calendar block

In the WordPress Block Editor, add **Event Calendar** (`simple-events-pro/event-calendar`). Its sidebar settings let editors choose an optional initial month in `YYYY-MM-DD` format and show or hide the category filter. The block previews the dynamic calendar inside the editor and retains the same AJAX month navigation on the published page.

### iCalendar export

Single events include automatic download and subscription links. Subscribe to the feed in Google Calendar, Outlook, Apple Calendar, etc.

**Subscription feed URL**: Users can add this to their calendar app:
```
<?php echo esc_url(Simple_Events_Pro_iCal::get_subscription_url()); ?>
```

**Single event download**: Each event page includes a download link that saves the event as `.ics`.

**Shortcode**: Add a subscription link anywhere:
```
[simple_events_subscribe]
```

## License

GPL-2.0-or-later
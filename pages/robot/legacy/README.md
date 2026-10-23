# Robot UI legacy implementations

These files are the **pre-redesign** robot tab implementations, preserved so we
can restore previous behaviour without digging through git history.

| File | Restores | Feature flag (default) |
|---|---|---|
| `info_chartjs_overview.php` | Overview with Chart.js donut / semi-dial gauges | `robot_ui/modern_overview` = **true** → new UI; set **false** to use this file |
| `architecture_fullbleed.php` | Architecture full-bleed table layout, ROS HTTP API only | `robot_ui/modern_architecture` = **true** → new UI; set **false** to use this file |

Flags are defined and documented in [`../ui_features.php`](../ui_features.php).

Do **not** delete these files as part of routine UI polish. If a legacy page is
truly retired, move it to git history intentionally and update `ui_features.php`.

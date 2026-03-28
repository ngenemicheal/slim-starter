# Cron Scripts

Place cron job scripts here.

On shared hosting, point cPanel's Cron Jobs to:
/home/username/public_html/cron/script-name.php

Scripts here should:

- Include bootstrap manually: require_once '../bootstrap/app.php'
- Not depend on HTTP context (no $\_SESSION, no headers)
- Be protected — do not expose these via public/ folder

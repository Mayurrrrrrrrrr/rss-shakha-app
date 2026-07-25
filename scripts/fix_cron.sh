#!/bin/bash
(crontab -l 2>/dev/null | grep -v 'send_evening_message.php'; echo '* * * * * sudo -u www-data sh -c "php /var/www/html/sanghasthan/scripts/send_evening_message.php >> /var/www/html/sanghasthan/storage/logs/evening_cron.log 2>&1"') | crontab -
crontab -l

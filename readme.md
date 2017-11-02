# About
- Integrations for Backlog Tasks and Slack Messages.

# Install
```
git clone https://github.com/yuki777/backlog-slack
php composer.phar install -vvv
chmod -R 777 storage bootstrap/cache
```

# Get Slack Webhook URL
- https://{YOURS}.slack.com/apps/A0F7XDUAZ-incoming-webhooks

# Set Webhook URL
- https://{YOURS}.backlog.com/settings/webhook/{YOURS}/create

# Get Backlog API Key
- https://{YOURS}.backlog.com/EditApiSettings.action

# Set .env
```
SLACK_ENDPOINT
SLACK_CHANNEL
BACKLOG_API_KEY
```

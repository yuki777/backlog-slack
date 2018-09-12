# これは
- Backlogの更新をトリガーにSlackに通知するツールです

# インストール
```
git clone https://github.com/yuki777/backlog-slack
cd backlog-slack
php composer.phar install -vvv
chmod -R 777 storage bootstrap/cache
```

# BacklogのAPIキーを取得します
- https://{YOURS}.backlog.com/EditApiSettings.action

# SlackのWebhook URLを取得します
- https://{YOURS}.slack.com/apps/A0F7XDUAZ-incoming-webhooks

# .envに設定します
```
SLACK_ENDPOINT
SLACK_CHANNEL
BACKLOG_API_KEY
```

# Backの設定でこのツールのURLを設定します
- https://{YOURS}.backlog.com/settings/webhook/{YOURS}/create
-  (例 : https://{YOURS}/api/hook )

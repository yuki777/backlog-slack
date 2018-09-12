# これは
- Backlogの更新をトリガーにSlackに通知するツールです

# インストール
- 適当に外部からアクセスできるところに設置します
```
git clone https://github.com/yuki777/backlog-slack
cd backlog-slack
php composer.phar install -vvv
chmod -R 777 storage bootstrap/cache
cp .env.sample .env
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

# Backlogの設定でこのツールを設置したURLを設定します
- https://{YOURS}.backlog.com/settings/webhook/{YOURS}/create
-  (例 : https://{YOURS}/api/hook )

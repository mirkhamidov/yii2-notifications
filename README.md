# TODO

* configure `components` from module
* configure `queueNotification` from module
* add requirements in composer.json
* slack notifications
* email notifications

# Configuration

in main app config file
```php
return [
    'bootstrap' => [
        'queueNotifications',
    ],
    'components' => [
        'queueNotifications' => [
            'class' => \yii\queue\db\Queue::class,
            'as log' => \yii\queue\LogBehavior::class,
            'db' => 'db', // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'notification', // Queue channel key
            'mutex' => \yii\mutex\PgsqlMutex::class, // Mutex that used to sync queries
            'mutexTimeout' => 0,
            'ttr' => 5 * 60, // Max time for anything job handling
            'attempts' => 5, // Max number of attempts
        ],
        'notifications' => [
            'class' => \mirkhamidov\notifications\Notifications::class,
        ],
    ],
    'modules' => [
        'notifications' => [
            'class' => mirkhamidov\notifications\Module::class,
            'queueIn' => 'queueNotifications',
            'providers' => [
                'telegram' => [
                    'class' => \mirkhamidov\notifications\providers\Telegram::class,
                ],
            ],
        ],
    ],
];

```

## Logs to different file

In app config file

```
'components' => [
    'log' => [
        'targets' => [
            ...
            [
                'class' => 'yii\log\FileTarget',
                'categories' => [
                    'mirkhamidov\notifications\providers\*',
                ],
                'logFile' => '@app/runtime/logs/notification-providers.log',
                'logVars' => [],
                'prefix' => function ($message) {
                    return '';
                }
            ],
        ],
    ],
],
```

# Examples

## Send message

```
use mirkhamidov\notifications\providers\TelegramProvider;

$msg = 'any message';
\Yii::$app->notifications->send($msg, TelegramProvider::ID, [
    'providerParams' => [
        'chat_id' => {CHAT_ID},
    ],
]);
```

More `providerParams` look at [Telegram SendMessage API](https://core.telegram.org/bots/api#sendmessage)


## Message with file

````
use mirkhamidov\notifications\providers\TelegramProvider;

$msg = 'any message';

\Yii::$app->notifications->send($msg, TelegramProvider::ID, [
    'providerParams' => [
        'chat_id' => Yii::$app->params['telegram']['miroff'],
        'file' => $model->getPdfFilePath(),
        'fileParams' => [
            // custom params
            ['fileType' => TelegramProvider::FILE_TYPE_DOCUMENT,]
            ['messageMergeType' => TelegramProvider::FILE_MESSAGE_MERGE_TYPE_AS_REPLY,]

            // any other Telegram API params, see below
            ['disable_notification' => true,]
        ],
    ],
]);
````

* `file` full path to file
* `fileParams` params to manage with file
    * `messageMergeType`
        * TelegramProvider::FILE_MESSAGE_MERGE_TYPE_AS_NO_MERGE **default** send message and file as separate messages
        * TelegramProvider::FILE_MESSAGE_MERGE_TYPE_AS_REPLY send file as reply for sent message
    * `fileType`
        * TelegramProvider::FILE_TYPE_DOCUMENT **default** [Telegram API for more params](https://core.telegram.org/bots/api#senddocument)
        * TelegramProvider::FILE_TYPE_PHOTO [Telegram API for more params](https://core.telegram.org/bots/api#sendphoto)
        * TelegramProvider::FILE_TYPE_AUDIO [Telegram API for more params](https://core.telegram.org/bots/api#sendaudio)
        * TelegramProvider::FILE_TYPE_VIDEO [Telegram API for more params](https://core.telegram.org/bots/api#sendvideo)

To send only file (without message) just set `$mgs` to `null`
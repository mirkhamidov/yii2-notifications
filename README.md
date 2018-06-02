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
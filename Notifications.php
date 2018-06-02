<?php

namespace mirkhamidov\notifications;

use function get_class;
use mirkhamidov\notifications\models\NotificationsModel;
use mirkhamidov\notifications\providers\iProvider;
use mirkhamidov\notifications\tasks\QueueNotificationTask;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\queue\cli\Queue;

/**
 * Class Notifications
 * @package mirkhamidov\notifications
 *
 * @property Module $module
 */
class Notifications extends Component
{
    /** @var string Modul's name from config of the app */
    public $moduleId = 'notifications';

    /** @var Module */
    private $_module;

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        if (!Yii::$app->hasModule($this->moduleId)) {
            throw new InvalidConfigException(Yii::t('notifications', 'There is no configured module "{moduleId}"', [
                'moduleId' => $this->moduleId,
            ]));
        }
        $this->_module = Yii::$app->getModule($this->moduleId);

        if (empty(Yii::$app->queue)) {
            throw new InvalidConfigException(Yii::t('notifications', 'Queue is not configured'));
        }
    }

    /**
     *
     * Example for params!
     *  [
     *      [providerParams] => [
     *          // optional
     *          // params for provider which transmits on create object
     *      ],
     *  ]
     *
     * @param $message
     * @param string $type устанавливается провайдер для отсылки сообщения
     * @param array $params see explanation above
     * @return mixed|true true=success
     * @throws InvalidConfigException
     */
    public function send($message, string $type, array $params = [])
    {
        $_providerParams = [];
        $_fullParams = $params;
        if (!empty($params['providerParams'])) {
            $_providerParams = $params['providerParams'];
            unset($params['providerParams']);
        }

        /** @var iProvider $provider */
        $provider = $this->module->getProvider($type);

        // todo: finish it
//        $transaction = Yii::$app->db->beginTransaction();

        // is it will be sent via queue or direct
        $isQueue = (!empty($this->module->queueIn));

        $newEntry = new NotificationsModel();
        $newEntry->status = NotificationsModel::STATUS_QUEUED;
        $newEntry->type = get_class($provider);
        $newEntry->message = $message;
        $newEntry->params = [
            'fullParams' => $_fullParams,
            'sysParams' => [
                'isQueue' => $isQueue,
            ],
            'providerType' => $type,
            'providerParams' => $_providerParams,
        ];

        if (!$newEntry->save()) {
            return $newEntry->firstErrors;
        }

        if ($isQueue) {
            /** @var Queue $queue */
            $queue = Yii::$app->get($this->module->queueIn);
            $jobId = $queue->push(new QueueNotificationTask([
                'notificationId' => $newEntry->id,
            ]));
            $newEntry->params = ArrayHelper::merge($newEntry->params, [
                'sysParams' => [
                    'queue' => [
                        'jobId' => $jobId,
                        'moduleId' => $this->moduleId,
                    ],
                ],
            ]);
            $newEntry->update(false);
            return true;
        } else {

            return $provider->send($newEntry);
        }
    }


    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->_module;
    }
}
<?php
/**
 * Executes delayed send notifications
 */
namespace mirkhamidov\notifications\tasks;

use mirkhamidov\notifications\models\NotificationsModel;
use mirkhamidov\notifications\Module;
use mirkhamidov\notifications\providers\iProvider;
use mirkhamidov\notifications\providers\TelegramProvider;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\queue\RetryableJobInterface;

class QueueNotificationTask extends BaseObject implements RetryableJobInterface
{
    public $notificationId;

    /** @var NotificationsModel */
    private $notificationModel;

    /** @var Module */
    private $_module;


    /** @inheritdoc */
    public function execute($queue)
    {
        $this->log("Started with notificationId: {$this->notificationId}");

        $this->notificationModel = NotificationsModel::findOne($this->notificationId);

        if (!$this->notificationModel) {
            $this->log("Model \"NotificationsModel\" with id \"{$this->notificationId}\" Not found", 'error');
            return false;
        }

        $providerName = $this->notificationModel->params['providerType'];
        $providerParams = [];
        if (!empty($this->notificationModel->params['providerParams'])
            && is_array($this->notificationModel->params['providerParams'])
        ) {
            $providerParams = $this->notificationModel->params['providerParams'];
        }
        /** @var iProvider|TelegramProvider $provider */
        $provider = $this->getModule()->getProvider($providerName, $providerParams);

        $provider->send($this->notificationModel);

        $this->log("Ended with notificationId: {$this->notificationId}");
    }

    /**
     * local logger
     * @param $data
     * @param string $type
     */
    private function log($data, $type='info')
    {
        Yii::{$type}($data, 'QueueNotificationTask');
    }

    /** @inheritdoc */
    public function getTtr()
    {
        return 15 * 60;
    }

    /** @inheritdoc */
    public function canRetry($attempt, $error)
    {
        return ($attempt < 5);
    }

    /**
     * @return Module
     * @throws InvalidConfigException
     */
    public function getModule()
    {
        if (!$this->_module) {
            $moduleId = $this->notificationModel->params['sysParams']['queue']['moduleId'];
            if (!Yii::$app->hasModule($moduleId)) {
                throw new InvalidConfigException(Yii::t('notifications', 'There is no configured module "{moduleId}"', [
                    'moduleId' => $moduleId,
                ]));
            }
            $this->_module = Yii::$app->getModule($moduleId);
        }
        return $this->_module;
    }
}
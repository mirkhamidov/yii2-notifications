<?php

namespace mirkhamidov\notifications\providers;


use function get_class;
use mirkhamidov\notifications\models\NotificationsModel;
use mirkhamidov\telegramBot\TelegramBot;
use Yii;
use yii\base\BaseObject;

class TelegramProvider extends BaseObject implements iProvider
{
    const ID = 'telegram';

    /**
     * Configurations for mirkhamidov/yii2-bot-telegram
     * @var array
     */
    public $botConfig = [];

    /**
     * @var string If TelegrabBot already loaded in system
     */
    public $botInComponent = null;

    /**
     * @var TelegramBot for [getTgBot()]
     */
    private $_tgBot;

    /** @inheritdoc */
    public function send(NotificationsModel $model)
    {
        $model->status = NotificationsModel::STATUS_PROCESSING;
        $model->update(false);
        $res = $this->getTgBot()->sendMessage($model->message, $model->params['providerParams']['chat_id']);
        $model->response = $res;
        $model->status = NotificationsModel::STATUS_SUCCESS;
        $model->update(false);
        return true;
    }

    /**
     * @return TelegramBot|object
     * @throws \yii\base\InvalidConfigException
     */
    private function getTgBot()
    {
        if (!$this->_tgBot) {
            if ($this->botInComponent && Yii::$app->has($this->botInComponent)) {
                $this->_tgBot = Yii::$app->get($this->botInComponent);
            } else {
                $this->_tgBot = Yii::createObject(TelegramBot::class, $this->botConfig);
            }
        }
        return $this->_tgBot;
    }
}
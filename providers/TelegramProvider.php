<?php

namespace mirkhamidov\notifications\providers;


use mirkhamidov\notifications\models\NotificationsModel;
use mirkhamidov\telegramBot\TelegramBot;
use stdClass;
use Yii;
use yii\base\BaseObject;

class TelegramProvider extends BaseObject implements iProvider
{
    const ID = 'telegram';

    /** @var string The way how to bind document to message if message exists */
    const FILE_MESSAGE_MERGE_TYPE_AS_REPLY = 'reply'; // as a reply to message
    const FILE_MESSAGE_MERGE_TYPE_AS_NO_MERGE = 'no'; // as a different message

    /** @var string Document types */
    const FILE_TYPE_DOCUMENT = 'document';
    const FILE_TYPE_PHOTO = 'photo';
    const FILE_TYPE_AUDIO = 'audio';
    const FILE_TYPE_VIDEO = 'video';

    /** @var string Default the wat of binding the document to message */
    public $fileAndMessageMergeType = self::FILE_MESSAGE_MERGE_TYPE_AS_NO_MERGE;

    /** @var string Default Sending file Type */
    public $fileType = self::FILE_TYPE_DOCUMENT;

    public $chat_id;
    public $file;
    public $fileParams;

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

    /**
     * @var stdClass Last response of sent message
     */
    private $lastMessageResponse;

    /**
     * @var bool default=false, if needed to send only one file
     */
    private $withoutMessage = false;

    /** @inheritdoc */
    public function send(NotificationsModel $model)
    {
        $this->log('Telegram message sending processing');

        $model->status = NotificationsModel::STATUS_PROCESSING;
        $model->update(false);

        try {

            if (!empty($model->message)) {
                $this->lastMessageResponse = $this->getTgBot()->sendMessage($model->message, $this->chat_id);
            }

            if (empty($model->message) && !empty($this->file)) {
                /** will be send only file */
                $this->log('Only file will be sent');
                $this->withoutMessage = true;
            }

            if (!empty($this->file)) {
                $this->log('Has file, sending...');
                $fileResult = $this->sendFile($model);

                /** ERROR */
                if ($fileResult === false && $this->withoutMessage === true) {
                    $model->status = NotificationsModel::STATUS_FAIL;
                    $model->last_message = 'Nothing to send, see logs';
                    $model->update(false);
                    $this->log('HALT -> Nothing to send (no file and no message', 'error');
                    return true;
                }
            }

            /** ERROR */
            if (empty($this->lastMessageResponse)) {
                $model->status = NotificationsModel::STATUS_FAIL;
                $model->last_message = 'Something went wrong, no response data detected';
                $model->update(false);
                $this->log('HALT -> Something went wrong, no response data detected', 'error');
                return true;
            }

            $model->response = $this->lastMessageResponse;
            $model->status = NotificationsModel::STATUS_SUCCESS;
            $model->update(false);

            $this->log('Telegram message sending finished');
        } catch (\Exception $e) {
            $this->log('Exception: ' . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString(), 'error');
            $model->status = NotificationsModel::STATUS_FAIL;
            $model->last_message = $e->getMessage();
            $model->update(false);
            return false;
        }

        return true;
    }

    /**
     * Sending file via Telegram API
     * @param NotificationsModel $model
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    private function sendFile(NotificationsModel $model)
    {
        if (!is_file($this->file)
            || (!empty($this->lastMessageResponse) && $this->lastMessageResponse->ok != true)
        ) {
            return false;
        }
        /** default params */
        $_documentParams = [];


        /** Merge params */
        if (!empty($this->fileParams)
            && is_array($this->fileParams)
        ) {
            $_documentParams = array_merge($_documentParams, $this->fileParams);
        }

        if (!empty($_documentParams['fileType'])) {
            $this->fileType = $_documentParams['fileType'];
        }
        if (!empty($_documentParams['messageMergeType'])) {
            $this->fileAndMessageMergeType = $_documentParams['messageMergeType'];
            unset($_documentParams['messageMergeType']);
        }

        /** if reply needed */
        if (!$this->withoutMessage
            && $this->fileAndMessageMergeType == self::FILE_MESSAGE_MERGE_TYPE_AS_REPLY
            && !empty($this->lastMessageResponse->result->message_id)
        ) {
            $_documentParams['reply_to_message_id'] = $this->lastMessageResponse->result->message_id;
        }



        switch ($this->fileType) {
            case self::FILE_TYPE_AUDIO:
                $_documentParams['audio'] = $this->file;
                $_documentParams['chat_id'] = $this->chat_id;
                $res = $this->getTgBot()->sendAudio($_documentParams);
                break;
            case self::FILE_TYPE_PHOTO:
                $res = $this->getTgBot()->sendPhoto(
                    $this->file,
                    $this->chat_id,
                    $_documentParams);
                break;
            case self::FILE_TYPE_VIDEO:
                $_documentParams['video'] = $this->file;
                $_documentParams['chat_id'] = $this->chat_id;
                $res = $this->getTgBot()->sendVideo($_documentParams);
                break;
            case self::FILE_TYPE_DOCUMENT:
            default:
                $_documentParams['document'] = $this->file;
                $_documentParams['chat_id'] = $this->chat_id;
                $res = $this->getTgBot()->sendDocument($_documentParams);
                break;
        }


        if (empty($this->lastMessageResponse)) {
            $this->lastMessageResponse = $res;
        } else {
            $this->lastMessageResponse = ['mgs' => $this->lastMessageResponse, 'file' => $res];
        }
        return true;
    }

    /**
     * Logging
     * @param $data
     * @param string $type
     */
    private function log($data, $type = 'info')
    {
        Yii::$type($data, __CLASS__);
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
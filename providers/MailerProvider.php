<?php

namespace mirkhamidov\notifications\providers;

use Yii;
use mirkhamidov\notifications\models\NotificationsModel;
use yii\base\BaseObject;
use yii\swiftmailer\Mailer;
use yii\swiftmailer\Message;

class MailerProvider extends BaseObject implements iProvider
{
    const ID = 'default-mailer';

    public $charset = null;
    public $from = null;
    public $replyTo = null;
    public $to = null;
    public $cc = null;
    public $bcc = null;
    public $subject = null;
    public $textBody = null;
    public $htmlBody = null;
    public $attachFilePath = null;
    public $attachOptions = [];

    /**
     * @var null string|array|null the view to be used for rendering the message body. This can be:
     * @see https://www.yiiframework.com/doc/api/2.0/yii-mail-basemailer#compose()-detail
     */
    public $view = null;

    public $params = [];

    /** @var Message */
    private $message;

    /** @var Mailer */
    private $mailer;

    /** @var bool Flag, view availability to send message */
    private $maySend = false;

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        $this->mailer = Yii::$app->mailer;
    }

    /** @inheritdoc */
    public function send(NotificationsModel $model)
    {
        $this->log('Default-mailer message sending processing');
        $model->status = NotificationsModel::STATUS_PROCESSING;
        $model->update(false);

        $this->message = $this->mailer->compose($this->view, $this->params);

        if (!empty($this->from)
            && !empty($this->to)
        ) {
            $this->maySend = true;
        } else {
            if (empty($this->from)) {
                $this->log('"from" data is empty', 'error');
            }
            if (empty($this->to)) {
                $this->log('"to" data is empty', 'error');
            }
        }

        if ($this->maySend === true) {

            if ($this->charset !== null) {
                $this->message->setCharset($this->charset);
            }
            if ($this->from !== null) {
                $this->message->setFrom($this->from);
            }
            if ($this->replyTo !== null) {
                $this->message->setReplyTo($this->replyTo);
            }
            if ($this->to !== null) {
                $this->message->setTo($this->to);
            }
            if ($this->cc !== null) {
                $this->message->setCc($this->cc);
            }
            if ($this->bcc !== null) {
                $this->message->setBcc($this->bcc);
            }
            if ($this->subject !== null) {
                $this->message->setSubject($this->subject);
            }
            if ($this->textBody !== null) {
                $this->message->setTextBody($this->textBody);
            }
            if ($this->htmlBody !== null) {
                $this->message->setHtmlBody($this->htmlBody);
            }

            if ($this->attachFilePath !== null) {
                $this->message->attach($this->attachFilePath, $this->attachOptions);
            }


            $this->message->send();
            $this->log('Message send');
            $model->status = NotificationsModel::STATUS_SUCCESS;
        }

        if ($this->maySend === false) {
            $this->log('Nothing to send, not enough data');
            $model->status = NotificationsModel::STATUS_FAIL;
            $model->last_message = 'Nothing to send, not enough data';
        }

        if (!$this->maySend && empty($model->last_message)) {
            $model->status = NotificationsModel::STATUS_FAIL;
            $model->last_message = 'Something went wrong';
        }

        $model->update(false);

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
}
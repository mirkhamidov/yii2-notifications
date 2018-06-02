<?php

namespace mirkhamidov\notifications\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "{{%notifications}}".
 *
 * @property int $id
 * @property string $status
 * @property string $type Provider`s full class name
 * @property string $message
 * @property array $params
 * @property array $response
 * @property string $last_message
 * @property string $created_at
 * @property string $updated_at
 */
class NotificationsModel extends \yii\db\ActiveRecord
{
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%notifications}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'type'], 'required'],
            [['message'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['status'], 'string', 'max' => 50],
            [['type', 'last_message'], 'string', 'max' => 255],
        ];
    }

    /** @inheritdoc */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('notifications', 'ID'),
            'status' => Yii::t('notifications', 'Status'),
            'type' => Yii::t('notifications', 'Type'),
            'message' => Yii::t('notifications', 'Message'),
            'params' => Yii::t('notifications', 'Params'),
            'response' => Yii::t('notifications', 'Response'),
            'last_message' => Yii::t('notifications', 'Last Message'),
            'created_at' => Yii::t('notifications', 'Created At'),
            'updated_at' => Yii::t('notifications', 'Updated At'),
        ];
    }

    /**
     * @inheritdoc
     * @return \mirkhamidov\notifications\models\query\NotificationsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \mirkhamidov\notifications\models\query\NotificationsQuery(get_called_class());
    }
}
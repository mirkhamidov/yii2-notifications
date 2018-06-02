<?php

use yii\db\Migration;

/**
 * Handles the creation of table `notifications`.
 */
class m180507_115601_create_notifications_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('notifications', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'status' => $this->string(50)->notNull(),
            'type' => $this->string()->notNull()->comment('Provider`s full class name'),
            'message' => $this->text(),
            'params' => 'jsonb NOT NULL DEFAULT \'{}\'',
            'response' => 'jsonb NOT NULL DEFAULT \'{}\'',
            'last_message' => $this->string()->null(),
            'created_at' => $this->timestamp()->null(),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->createIndex(
            'idx-notifications-status'
            , 'notifications'
            , 'status'
        );

        $this->createIndex(
            'idx-notifications-type'
            , 'notifications'
            , 'type'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-notifications-status', 'notifications');
        $this->dropTable('notifications');
    }
}

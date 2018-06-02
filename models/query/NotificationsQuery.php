<?php

namespace mirkhamidov\notifications\models\query;

/**
 * This is the ActiveQuery class for [[\mirkhamidov\notifications\models\Notifications]].
 *
 * @see \mirkhamidov\notifications\models\NotificationsModel
 */
class NotificationsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return \mirkhamidov\notifications\models\NotificationsModel[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \mirkhamidov\notifications\models\NotificationsModel|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}

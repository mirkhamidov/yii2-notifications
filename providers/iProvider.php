<?php

namespace mirkhamidov\notifications\providers;


use mirkhamidov\notifications\models\NotificationsModel;

interface iProvider
{
    /**
     * Method which sends info
     * @param NotificationsModel $model Info About sending data
     * @return mixed
     */
    public function send(NotificationsModel $model);
}
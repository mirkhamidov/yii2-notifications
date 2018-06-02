<?php

namespace mirkhamidov\notifications;

use mirkhamidov\notifications\providers\iProvider;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

class Module extends \yii\base\Module
{
    const TRANSLATION_CATEGORY = 'notifications';
    /**
     * Providers configuration
     * @var array
     */
    public $providers = null;

    /**
     * In which id-component-name is queue is located
     * @var string
     */
    public $queueIn = null;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();
    }


    /**
     * @param $provider
     * @param array $params
     * @return iProvider|object
     * @throws InvalidConfigException
     */
    public function getProvider($provider, array $params = [])
    {
        if ($this->providers === null) {
            throw new InvalidConfigException(Yii::t('notifications', 'There is no configured providers for Notifications module'));
        }

        if (!isset($this->providers[$provider])) {
            throw new InvalidConfigException(Yii::t('notifications', 'Provider "{provider}" not configured.', [
                'provider' => $provider,
            ]));
        }
        return Yii::createObject(ArrayHelper::merge($this->providers[$provider], $params));
    }


    /**
     * Initializes language sources
     * @throws InvalidConfigException
     */
    public function registerTranslations()
    {
        if (!isset(Yii::$app->get('i18n')->translations[self::TRANSLATION_CATEGORY . '*'])) {
            Yii::$app->get('i18n')->translations[self::TRANSLATION_CATEGORY . '*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => __DIR__ . '/messages',
                'sourceLanguage' => 'en-US'
            ];
        }
    }
}
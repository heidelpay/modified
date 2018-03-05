<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 23.02.2018
 * Time: 09:25
 */
require_once(DIR_FS_EXTERNAL . 'heidelpay/lib/message-code-mapper/FileLoader.php');
FileLoader::requireAllLibs();

use Heidelpay\MessageCodeMapper\MessageCodeMapper;

/**
 * Class heidelpayMessageCodeHelper
 */
class heidelpayMessageCodeHelper
{
    /**
     * @var string code fore the default locale
     */
    public $defaultLocale;
    /**
     * @var array contains the mapping between language code and the locale
     */
    public $languages;

    /**
     * Set up enabled languages and default.
     * heidelpayMessageCodeHelper constructor.
     */
    public function __construct()
    {
        $this->languages = [
            'de' => 'de_DE',
            'en' => 'en_US'
        ];
        $this->defaultLocale = $this->languages['de'];
    }

    /**
     * @param string $errorCode
     * @param string $languageCode Language that should be used. If not found, the default is used
     * @return string
     */
    public function getMessage($errorCode, $languageCode)
    {
        $locale = $this->getLocale($languageCode);

        if ($locale) {
            $mapper = new MessageCodeMapper($locale);
        } else {
            $mapper = new MessageCodeMapper($this->defaultLocale);
        }

        return $mapper->getMessage($errorCode);
    }

    /**
     * Provides the locale that matches the language code
     * @param $languageCode
     * @return mixed|null
     */
    private function getLocale($languageCode)
    {
        if (!empty($this->languages[$languageCode])) {
            return $this->languages[$languageCode];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @param array $languages
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }

    /**
     * @return string
     */
    public function getDefaultLanguage()
    {
        return $this->defaultLocale;
    }

    /**
     * @param string $defaultLanguage
     */
    public function setDefaultLanguage($defaultLanguage)
    {
        $this->defaultLocale = $defaultLanguage;
    }
}
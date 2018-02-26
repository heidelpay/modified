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

class heidelpayMessageCodeHelper
{
    public $defaultLanguage;
    public $languages;

    public function __construct()
    {
        $this->languages = [
            'de' => 'de_DE',
            'en' => 'en_US'
        ];
        $this->defaultLanguage = $this->languages['de'];
    }

    public function getMessage($code, $languageCode)
    {
        $locale = $this->mapLanguage($languageCode);

        if ($locale) {
            $mapper = new MessageCodeMapper($locale);
        } else {
            $mapper = new MessageCodeMapper($this->defaultLanguage);
        }

        return $mapper->getMessage($code);
    }

    private function mapLanguage($languageCode)
    {
        if (!empty($this->languages[$languageCode])) {
            return htmlspecialchars_decode($this->languages[$languageCode]);
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @param mixed $languages
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }

    /**
     * @return mixed
     */
    public function getDefaultLanguage()
    {
        return $this->defaultLanguage;
    }

    /**
     * @param mixed $defaultLanguage
     */
    public function setDefaultLanguage($defaultLanguage)
    {
        $this->defaultLanguage = $defaultLanguage;
    }
}
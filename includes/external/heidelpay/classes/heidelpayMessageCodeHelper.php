<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 23.02.2018
 * Time: 09:25
 */
require_once(DIR_FS_EXTERNAL . 'heidelpay/lib/message-code-mapper/FileLoader.php');
FileLoader::requireAllLibs();

use \Heidelpay\MessageCodeMapper\MessageCodeMapper;

class heidelpayMessageCodeHelper
{
    public static function getMessage($code, $languageCode = 'de')
    {
        $locale = self::mapLanguage($languageCode);

        if($locale) {
            $mapper = new MessageCodeMapper($locale);
        } else {
            $mapper = new MessageCodeMapper();
        }

        return $mapper->getMessage($code);
    }

    private static function mapLanguage($languageCode)
    {
        //TODO:try to avoid static data
        $languages = [
            'de' => 'de_DE',
            'en' => 'en_US'
        ];

        if (!empty($languages[$languageCode])) {
            return $languages[$languageCode];
        }

        return null;
    }
}
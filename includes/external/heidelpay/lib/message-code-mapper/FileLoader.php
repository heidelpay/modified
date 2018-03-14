<?php
/**
 * Short Summary
 *
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/heidelpay-php-api/
 *
 * @author  Simon Gabriel <simon.gabriel@heidelpay.de>
 *
 * @package  Heidelpay
 * @subpackage PhpStorm
 * @category ${CATEGORY}
 */

class FileLoader
{
    const BASE_PATH = __DIR__.DIRECTORY_SEPARATOR. 'lib';

    /**
     * Loads all php files in lib directory.
     *
     * @param string $dir
     */
    public static function requireAllLibs($dir = self::BASE_PATH)
    {
        /** @var array $files */
        $files = glob("$dir/*");
        foreach ($files as $file) {
            if (preg_match('/\.php$/', $file)) {
                require_once $file;
            } elseif (is_dir($file)) {
                self::requireAllLibs($file);
            }
        }
    }
}
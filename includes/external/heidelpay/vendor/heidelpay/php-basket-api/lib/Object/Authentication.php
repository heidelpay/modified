<?php

namespace Heidelpay\PhpBasketApi\Object;

use Heidelpay\PhpBasketApi\Exception\ParameterOverflowException;

/**
 * Authentication object for the heidelpay basket api
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Jens Richter <development@heidelpay.com>
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\Object
 */
class Authentication extends AbstractObject
{
    /**
     * @var int Max character count for the Sender ID
     */
    const MAX_SENDER_LENGTH = 32;

    /**
     * @var string user login
     */
    protected $login;

    /**
     * @var string user password
     */
    protected $password;

    /**
     * @var string sender id
     */
    protected $sender;

    /**
     * Authentication constructor.
     *
     * @param string $login
     * @param string $password
     * @param string $senderId
     *
     * @throws ParameterOverflowException
     */
    public function __construct($login = null, $password = null, $senderId = null)
    {
        $this->setLogin($login);
        $this->setPassword($password);
        $this->setSender($senderId);
    }

    /**
     * Sets the user login
     *
     * @param string $login
     *
     * @return Authentication
     */
    public function setLogin($login)
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Sets the user password
     *
     * @param string $password
     *
     * @return Authentication
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Sets the Sender ID.
     *
     * @param string $sender
     *
     * @throws ParameterOverflowException
     *
     * @return Authentication
     */
    public function setSender($sender)
    {
        if (strlen($sender) > $this::MAX_SENDER_LENGTH) {
            throw new ParameterOverflowException(
                'Sender ID cannot be longer than ' . $this::MAX_SENDER_LENGTH . ' characters.'
            );
        }

        $this->sender = $sender;
        return $this;
    }

    /**
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'login' => $this->login,
            'password' => $this->password,
            'sender' => $this->sender,
        ];
    }
}

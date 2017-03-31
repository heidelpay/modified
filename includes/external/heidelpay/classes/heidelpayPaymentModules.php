<?php
/**
 * Sepa direct debit payment method class
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/modified/
 *
 * @package  heidelpay
 * @subpackage modified
 * @category modified
 */

class heidelpayPaymentModules
{
    /** @var  $code string current payment code */
    public $code;
    public $title;
    public $description;
    public $enabled;
    public $hp;
    public $payCode;
    public $tmpStatus;
    protected $tmpOrders = false;
    /** @var  $order  */
    protected $order;

    /**
     * heidelpay payment constructor
     */
    public function __construct()
    {
        global $order;
        $this->order = $order;
        $this->hp = new heidelpay();
        $this->hp->actualPaymethod = strtoupper($this->payCode);
        $this->version = $this->hp->version;

        if (is_object($order)) {
            $this->update_status();
        }
    }

    public function update_status()
    {

    }

    /**
     * javascript validation
     * @return bool
     */
    public function javascript_validation()
    {
        return false;
    }

    public function selection()
    {
        global $order;
    }

    public function pre_confirmation_check()
    {
        global $order;
    }

    public function confirmation()
    {
        return false;
    }

    public function process_button()
    {
        global $order;
        return false;
    }

    public function payment_action()
    {
        return true;
    }

    public function before_process()
    {
        return false;
    }

    public function after_process()
    {
        global $order, $xtPrice, $insert_id;
        return true;
    }

    public function admin_order($oID)
    {
        return false;
    }

    public function get_error()
    {
        global $_GET;
    }

    public function check()
    {
    }

    public function install()
    {
    }

    public function remove($install = false)
    {
        xtc_db_query("delete from " . TABLE_CONFIGURATION
            . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    public function keys()
    {
    }
}

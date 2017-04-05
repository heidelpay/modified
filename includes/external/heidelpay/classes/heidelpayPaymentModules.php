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
    /** @var  $order */
    protected $order;
    /** @var string $transactionMode transaction mode for this payment method */
    protected $transactionMode;
    /** @var  string $order_status */
    protected $order_status;

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
        $this->transactionMode = constant('MODULE_PAYMENT_HP'.strtoupper($this->payCode).'_TRANSACTION_MODE');

        if (is_object($order)) {
            $this->update_status();
        }
    }

    public function update_status()
    {
    }

    /**
     * javascript validation
     *
     * @return bool
     */
    public function javascript_validation()
    {
        return false;
    }

    public function selection()
    {
        // update order instance
        global $order;
        $this->order = $order;

        // if transaction mode is live return empty array
        if ($this->transactionMode === 'LIVE') {
            return array();
        }

        // if transaction mode is test return a warning text
       return array(
            array(
                'title' => '',
                'field' => '<strong style="color: darkred">'
                    .constant('MODULE_PAYMENT_HP'.strtoupper($this->payCode).'_DEBUGTEXT')
                .'</strong>'
            )
        );
    }

    /**
     * weather this payment method is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        // load current settings

        // min/max amount for this payment method
        if ($this->isAmountToHigh()) {
            return false;
        }
        if ($this->isAmountToLow()) {
            return false;
        }

        return $this->canCustomerUseThisPaymentMethod();
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
        $this->hp->rememberOrderData($order);
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

    /**
     * install default config settings to database
     *
     * @param array $configSettings configuration option
     */
    public function defaultConfigSettings($configSettings=array())
    {
        $groupId = 6;
        $sqlBase = 'INSERT INTO `' . TABLE_CONFIGURATION . '` SET ';
        foreach ($configSettings as $configKey => $configValue) {
            $sql = $sqlBase . ' ';
            foreach ($configValue as $key => $val) {
                $sql .= '`' . addslashes($key) . '` = "' . $val . '", ';
            }
            $sql .= '`sort_order` = "' . $configKey . '", ';
            $sql .= '`configuration_group_id` = "' . addslashes($groupId) . '", ';
            $sql .= '`date_added` = NOW() ';
            xtc_db_query($sql);
        }
    }

    public function remove()
    {
        xtc_db_query("delete from " . TABLE_CONFIGURATION
            . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    /**
     * get the total amount of an order
     *
     * @return integer total amount including tax
     */
    public function getOrderAmountSelection()
    {
        // estimate total amount
        $total = $this->order->info['total'];

        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0
            && $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1
        ) {
            // total amount including tax
            $total = $this->order->info['total'] + $this->order->info['tax'];
        }

        return $total * 100;
    }

    /**
     * Estimate if the oder amount is to high
     *
     * @return bool true if amount to high
     *
     * @param null|mixed $order
     * @param mixed      $maxAmount
     */
    public function isAmountToHigh($order = null, $maxAmount = 0)
    {
        // return false on an empty order object
        if ($this->order === null) {
            return true;
        }

        $totalAmount = $this->getOrderAmountSelection();

        if ($maxAmount > 0 && $maxAmount < $totalAmount) {
            return true;
        }
        return false;
    }

    /**
     * Estimate if the oder amount is to low
     *
     * @return bool true if amount to low
     */
    public function isAmountToLow()
    {
        // return false on an empty order object
        if ($this->order === null) {
            return true;
        }

        $min = constant('MODULE_PAYMENT_HP'.strtoupper($this->payCode).'_MIN_AMOUNT');

        $totalAmount = $this->getOrderAmountSelection();

        if ($min > 0 && $min > $totalAmount) {
            return true;
        }
        return false;
    }

    /**
     * return true if the customer can use this payment method
     *
     * @return bool return true if the customer can use this payment method
     */
    public function canCustomerUseThisPaymentMethod()
    {
        $testAccount = strtolower(constant('MODULE_PAYMENT_HP'.strtoupper($this->payCode).'_TEST_ACCOUNT'));


        if ($this->transactionMode === 'LIVE') {
            return true;
        }
        if (strpos($testAccount, $this->order->customer['email_address']) !== false) {
            return true;
        }

        return false;
    }

    public function birthDateSelection()
    {
        // Birth day selection
        $formFields='<select title="birthday" name="hp'.$this->payCode.'[day]" >';
        $formFields.='<option value="">--</option>';

        for($day=1; $day <=31; $day++){
            $formFields.='<option value="'.$day.'">'.$day.'</option>';
        }

        $formFields.='</select>';

        // Birth mouth selection
        $formFields.='<select title="birthmonth" name="hp'.$this->payCode.'[month]" >';
        $formFields.='<option value="">--</option>';

        for($month=1; $month <=12; $month++){
            $formFields.='<option value="'.$month.'">'.$month.'</option>';
        }

        $formFields.='</select>';

        // Birth year selection
        $formFields.='<select title="birthyear" name="hp'.$this->payCode.'[year]" >';
        $formFields.='<option value="">--</option>';

        for ($year = 17; $year <= 80; $year++) {
            $yearNumber = date('Y', strtotime("last day of -$year year"));
            $formFields.='<option value="'.$yearNumber.'">'.$yearNumber.'</option>';
        }

        $formFields.='</select>';

        return array(
            'title' => constant('MODULE_PAYMENT_HP'.strtoupper($this->payCode).'_SALUTATION'),
            'field' => $formFields
        );

    }
}

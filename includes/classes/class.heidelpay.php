<?php

/**
 * heidelpay payment class
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
class heidelpay
{
    public $response = '';
    public $error = '';
    /** @var bool $hpdebug activate debugging */
    public $hpdebug = false;
    /** @var string $live_url_new url for the production payment system */
    public $live_url_new = 'https://heidelpay.hpcgw.net/sgw/gtw';
    /** @var string $live_url_new url for the sandbox payment system */
    public $demo_url_new = 'https://test-heidelpay.hpcgw.net/sgw/gtw';
    /** @var array $availablePayments all available payment codes */
    public $availablePayments = array(
        'CC',
        'DD',
        'DC',
        'VA',
        'OT',
        'IV',
        'PP',
        'UA',
        'PC'
    );
    public $pageURL = '';
    public $actualPaymethod = 'CC';
    public $url;
    /** @var string plugin version aka release date */
    public $version = '16.08.23';
    public $importantPPFields = array(
        'PRESENTATION_AMOUNT',
        'PRESENTATION_CURRENCY',
        'CONNECTOR_ACCOUNT_COUNTRY',
        'CONNECTOR_ACCOUNT_HOLDER',
        'CONNECTOR_ACCOUNT_NUMBER',
        'CONNECTOR_ACCOUNT_BANK',
        'CONNECTOR_ACCOUNT_BIC',
        'CONNECTOR_ACCOUNT_IBAN',
        'IDENTIFICATION_SHORTID'
    );

    /**
     * heidelpay constructor.
     */
    public function __construct()
    {
        ob_start();
        $this->pageURL = HTTPS_SERVER . '';
    }

    /**
     * send and prepare request for registrations
     *
     * @param $order
     * @param $payCode string payment code
     *
     * @return mixed|string
     */
    public function handleRegister($order, $payCode)
    {
        $this->trackStep('handleRegister', 'order', $order);
        $debug = false;
        if (constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_DEBUG') == 'True') {
            $debug = true;
        }
        $ACT_MOD_MODE = constant('MODULE_PAYMENT_HP' . strtoupper($this->actualPaymethod) . '_MODULE_MODE');
        if ($ACT_MOD_MODE == 'AFTER') {
            return false;
        }

        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 &&
            $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1
        ) {
            $total = $order->info['total'] + $order->info['tax'];
        } else {
            $total = $order->info['total'];
        }

        $user_id = $_SESSION['customer_id'];
        $orderId = 'User ' . $user_id . '-' . date('YmdHis');
        $amount = $total;
        $currency = $order->info['currency'];
        $language = strtoupper($_SESSION['language_code']);
        $userData = array(
            'firstname' => $order->billing['firstname'],
            'lastname' => $order->billing['lastname'],
            'salutation' => ($order->customer['gender'] == 'f' ? 'MRS' : 'MR'),
            'street' => $order->billing['street_address'],
            'zip' => $order->billing['postcode'],
            'city' => $order->billing['city'],
            'country' => $order->billing['country']['iso_code_2'],
            'email' => $order->customer['email_address'],
            'ip' => $_SERVER['REMOTE_ADDR']
        );
        $payMethod = 'RG';
        $data = $this->prepareData($orderId, $amount, $currency, $payCode, $userData, $language, $payMethod);
        $this->trackStep('handleRegister', 'data', $data);
        if ($debug) {
            echo '<pre>' . print_r($data, 1) . '</pre>';
        }
        $res = $this->doRequest($data);
        $this->trackStep('handleRegister', 'result', $res);
        if ($debug) {
            echo 'System-URL: ' . $this->url;
        }
        if ($debug) {
            echo '<pre>resp(' . print_r($this->response, 1) . ')</pre>';
        }
        if ($debug) {
            echo '<pre>' . print_r($res, 1) . '</pre>';
        }
        $res = $this->parseResult($res);
        $this->trackStep('handleRegister', 'parsedResult', $res);
        if ($debug) {
            echo '<pre>' . print_r($res, 1) . '</pre>';
        }
        $processingresult = $res['result'];
        $redirectURL = $res['url'];
        $base = 'heidelpay_redirect.php?';
        $src = $base . "payment_error=hp" . strtolower($this->actualPaymethod) . '&error='
            . $res['all']['PROCESSING.RETURN'] . '&' . session_name() . '=' . session_id();
        if ($processingresult == "ACK" && strstr($redirectURL, "http")) {
            $src = $redirectURL;
        }
        $this->trackStep('handleRegister', 'src', $src);
        if ($debug) {
            echo $src;
            exit();
        }
        return $src;
    }

    /**
     * prepare and send request to heidelpay for authorise or debit
     *
     * @param $order
     * @param $payCode
     * @param bool $insertId
     *
     * @return string
     */
    public function handleDebit($order, $payCode, $insertId = false)
    {
        $this->trackStep('handleDebit', 'order', $order);
        $debug = false;
        if (constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_DEBUG') == 'True') {
            $debug = true;
        }
        $loc = '';
        if (SEARCH_ENGINE_FRIENDLY_URLS == 'true') {
            $loc = DIR_WS_CATALOG;
        }
        $ACT_MOD_MODE = @constant('MODULE_PAYMENT_HP' . strtoupper($this->actualPaymethod) . '_MODULE_MODE');
        if (!in_array($ACT_MOD_MODE, array(
            'DIRECT',
            'AFTER',
            'NOWPF'
        ))
        ) {
            $ACT_MOD_MODE = 'AFTER';
        }
        $ACT_PAY_MODE = @constant('MODULE_PAYMENT_HP' . strtoupper($this->actualPaymethod) . '_PAY_MODE');
        if (!in_array($ACT_PAY_MODE, array(
            'DB',
            'PA'
        ))
        ) {
            $ACT_PAY_MODE = 'DB';
        }

        $userId = $_SESSION['hpLastData']['user_id'];
        if ($userId <= 0) {
            $userId = $_SESSION['customer_id'];
        } // Fallback User Id
        $orderId = 'User ' . $userId . '-' . date('YmdHis');
        if (!empty($insertId)) {
            $orderId = 'User ' . $userId . ' Order ' . $insertId;
        }
        $amount = $_SESSION['hpLastData']['amount'];
        $currency = $_SESSION['hpLastData']['currency'];
        $language = $_SESSION['hpLastData']['language'];
        $userData = $_SESSION['hpLastData']['userData'];
        if ($debug) {
            echo '<pre>SESSION: ' . print_r($_SESSION['hpLastData'], 1) . '</pre>';
        }
        $capture = false;
        if (strtoupper($payCode) == 'DD') {
            $ACT_MOD_MODE = 'DIRECT';
        }
        if ($ACT_MOD_MODE == 'DIRECT') {
            $capture = true;
        }

        // Special CC Reuse
        if (!empty($_SESSION['hpUseUniqueId']) && ((strtoupper($payCode) == 'CC') || (strtoupper($payCode) == 'DC'))) {
            $capture = true;
            $_SESSION['hpUniqueID'] = $_SESSION['hpUseUniqueId'];
        } else {
            unset($_SESSION['hpUniqueID']);
        }

        $payMethod = $ACT_PAY_MODE;
        $changePayType = array(
            'gp',
            'su',
            'tp',
            'idl',
            'eps'
        );
        if (in_array($payCode, $changePayType)) {
            $payCode = 'OT';
        }
        if ($payCode == 'ppal') {
            $payCode = 'VA';
        }
        if (empty($payMethod)) {
            $payMethod = 'DB';
        }
        if ($payCode == 'OT' && $payMethod == 'DB') {
            $payMethod = 'PA';
        }

        if (in_array(strtoupper($payCode), array(
            'OT',
            'PP',
            'IV'
        ))) {
            if ($payMethod == 'DB') {
                $payMethod = 'PA';
            } // set invoice and prepayment always to transaction mode authorise
            if (in_array(strtoupper($payCode), array(
                'PP',
                'IV'
            ))) {
                $capture = true;
            } // use invoice and prepayment always without Iframe
            unset($_SESSION['hpUniqueID']);
        }
        // prepare the post payload for the api request
        $data = $this->prepareData(
            $orderId,
            $amount,
            $currency,
            $payCode,
            $userData,
            $language,
            $payMethod,
            $capture,
            $_SESSION['hpUniqueID']
        );
        $this->trackStep('handleDebit', 'data', $data);


        if ($debug) {
            echo '<pre>' . print_r($data, 1) . '</pre>';
        }

        // send api request to heidelpay api
        $res = $this->doRequest($data);
        $this->trackStep('handleDebit', 'result', $res);
        if ($debug) {
            echo '<pre>resp(' . print_r($this->response, 1) . ')</pre>';
        }
        if ($debug) {
            echo '<pre>' . print_r($res, 1) . '</pre>';
        }

        // parse the heidelpay result
        $res = $this->parseResult($res);
        $this->trackStep('handleDebit', 'parsedResult', $res);
        if ($debug) {
            echo '<pre>' . print_r($res, 1) . '</pre>';
        }
        $_SESSION['HEIDELPAY_IFRAME'] = false;

        if (isset($res['all']['ACCOUNT.HOLDER']) && ($res['all']['ACCOUNT.HOLDER'] != '')) {
            $holder = $res['all']['ACCOUNT.HOLDER'];
        }

        if (strpos($res['all']['PAYMENT.CODE'], 'DD') === true) {
            // save direct debit payment data
            $this->saveMEMO($userId, 'heidelpay_last_iban', $res['all']['ACCOUNT.IBAN']);
            $this->saveMEMO($userId, 'heidelpay_last_holder', $res['all']['ACCOUNT.HOLDER']);
        }

        // 3D Secure
        if ($res['all']['PROCESSING.STATUS.CODE'] == '80' &&
            $res['all']['PROCESSING.RETURN.CODE'] == '000.200.000' &&
            $res['all']['PROCESSING.REASON.CODE'] == '00'
        ) {
            $src = $res['all']['PROCESSING.REDIRECT.URL'];
            if ($this->actualPaymethod == 'BS') {
                $hpIframe = '<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;'
                    . 'background: rgba(0,0,0,.5); z-index: 9998"></div>' . '<div style="position: absolute;'
                    . 'top: 0; left: 0; z-index: 9999"><iframe src="' . $src . '" allowtransparency="true"' .
                    ' frameborder="0" width="925" height="800" name="heidelpay_frame"></iframe></div>';
                header('Location: ' . $src);
                exit();
            }
            $hpIframe = '<iframe src="about:blank" frameborder="0" width="400" height="600" name="heidelpay_frame">'
                . '</iframe>';
            $hpIframe .= '<form method="post" action="' . $src . '" target="heidelpay_frame" id="heidelpay_form">';
            $hpIframe .= '<input type="hidden" name="TermUrl" value="'
                . $res['all']['PROCESSING.REDIRECT.PARAMETER.TermUrl'] . '">';
            $hpIframe .= '<input type="hidden" name="PaReq" value="'
                . $res['all']['PROCESSING.REDIRECT.PARAMETER.PaReq'] . '">';
            $hpIframe .= '<input type="hidden" name="MD" value="'
                . $res['all']['PROCESSING.REDIRECT.PARAMETER.MD'] . '">';
            $hpIframe .= '</form>';
            $hpIframe .= '<script>document.getElementById("heidelpay_form").submit();</script>';
            if (@constant('MODULE_PAYMENT_HP' . strtoupper($this->actualPaymethod) . '_DIRECT_MODE')
                == 'GAMBIOLIGHTBOX'
            ) {
                global $smarty;
                $smarty->assign('LIGHTBOX', gm_get_conf('GM_LIGHTBOX_CHECKOUT'));
                if ($_SESSION['style_edit_mode'] == 'edit') {
                    $smarty->assign('STYLE_EDIT', 1);
                } else {
                    $smarty->assign('STYLE_EDIT', 0);
                }
                $smarty->assign('language', $_SESSION['language']);
                $smarty->assign('content', '<center>' . $hpIframe . '</center>');
                $content = $smarty->fetch(CURRENT_TEMPLATE . '/module/checkout_payment_hp.html');
                $_SESSION['HEIDELPAY_IFRAME'] = $content;
                if (!$debug) {
                    header('Location: ' . $loc . 'heidelpay_checkout_iframe.php?'
                        . session_name() . '=' . session_id());
                }
                exit();
            } elseif (@constant('MODULE_PAYMENT_HP' . strtoupper($this->actualPaymethod)
                    . '_DIRECT_MODE') == 'LIGHTBOX'
            ) {
                $hpIframeCode = '<center><div id="hpBox"><div style="background-color: #666; position:fixed;'
                    . ' display:block; margin:0; padding:0; top:0; left:0; opacity: 0.9; -moz-opacity: 0.9;'
                    . ' -khtml-opacity: 0.9; filter:alpha(opacity=90); z-index: 1000; width: 100%;'
                    . ' height: 100%;"></div>';
                $hpIframeCode .= '<div style="z-index: 1001; position: absolute; width: 800px; top: 50%;'
                    . ' left: 50%; margin-top: -325px; margin-left: -400px;">';
                $hpIframeCode .= $hpIframe;
                $hpIframeCode .= '<br><a href="" onClick="document.getElementById(\'hpBox\').style.display=\'none\';'
                    . ' return false;">close</a></div></div></center>';
                $_SESSION['HEIDELPAY_IFRAME'] = $hpIframeCode;
            } else {
                $_SESSION['HEIDELPAY_IFRAME'] = $hpIframe;
            }
            $_SESSION['hpLastPost'] = $_POST;
            if (empty($_SESSION['hpLastPost'])) {
                $_SESSION['hpLastPost']['hp'] = 1;
            }
            if ($debug) {
                echo '<pre>' . print_r($_SESSION['hpLastPost'], 1) . '</pre>';
            }
            // if($debug) echo '<pre>'.print_r($GLOBALS, 1).'</pre>';
            $this->trackStep('handleDebit', 'session', $_SESSION);
            if (!$debug) {
                header('Location: ' . $loc . 'heidelpay_3dsecure.php?' . session_name() . '=' . session_id());
            }
            exit();
        } elseif ($ACT_MOD_MODE == 'AFTER') {
            // $_SESSION['hpLastPost'] = $_POST;
        }
        $processingresult = $res['result'];
        $redirectURL = $res['url'];
        $base = 'heidelpay_redirect.php?';
        $src = $base . 'payment_error=hp' . strtolower($this->actualPaymethod);
        if ($processingresult != "ACK") {
            $src .= '&error=' . $res['all']['PROCESSING.RETURN'] . '&' . session_name() . '=' . session_id();
            $comment = $res['all']['PROCESSING.RETURN'];
            $status = constant('MODULE_PAYMENT_HP' . strtoupper($this->actualPaymethod) . '_CANCELED_STATUS_ID');
            $this->addHistoryComment($insertId, $comment, $status);
            $this->setOrderStatus($insertId, $status);
            $this->trackStep('handleDebit', 'src', $src);
            if (!$debug) {
                header('Location: ' . $loc . '' . $src);
            }
            if ($debug) {
                echo $src;
            }
            exit();
        } elseif ($processingresult == "ACK" && strstr($redirectURL, "http")) {
            $src = $redirectURL;
        } elseif // IDeal payment page
        (!empty($res['all']['PROCESSING.REDIRECT.URL'])
        ) {
            // IDeal und OT payment page
            if ($this->actualPaymethod == 'SU') {
                $form = '<form method="post" action="' . $res['all']['PROCESSING_REDIRECT_URL']
                    . '" id="hpSURedirectForm">';
                foreach ($res['all'] as $k => $v) {
                    if (strpos($k, 'PROCESSING_REDIRECT_PARAMETER_') !== false) {
                        $form .= '<input name="'
                            . preg_replace('/PROCESSING_REDIRECT_PARAMETER_/', '', $k)
                            . '" value="' . utf8_encode($v) . '" type="hidden">';
                    }
                }
                // $form.= '<input type="submit"><br>';
                $form .= '</form><script>document.getElementById("hpSURedirectForm").submit();</script>';
                $_SESSION['HEIDELPAY_IFRAME'] = $form;
                // echo '<pre>'.print_r($form, 1).'</pre>'; exit();
                if (!$debug) {
                    header('Location: ' . $loc . 'heidelpay_checkout_iframe.php?' . session_name()
                        . '=' . session_id());
                }
                if (!$debug) {
                    exit();
                }
                // if($debug) echo 'IFrame: '.$hpIframe.'<br>';
            } else {
                // Giropay kann direkt angesprungen werden
                $src = $res['all']['PROCESSING.REDIRECT.URL'];
                if (!$debug) {
                    header('Location: ' . $src . '');
                }
                if (!$debug) {
                    exit();
                }
            }
        }
        $this->trackStep('handleDebit', 'src', $src);
        if ($debug) {
            echo 'Src: ' . $src . '<br>';
        }
        $hpIframe = '';
        if (in_array($payCode, array(
                'cc',
                'dc',
                'dd'
            )) && ($ACT_MOD_MODE == 'DIRECT' || $ACT_MOD_MODE == 'NOWPF') && ($payMethod == 'DB' || $payMethod == 'PA')
        ) {
            // Bei DB fuer CC / DC / DD keinen IFrame anzeigen
            if (!$_SESSION['HEIDELPAY_IFRAME'] && $processingresult == "ACK" && $insertId > 0) {
                $comment = 'ShortID: ' . $res['all']['IDENTIFICATION.SHORTID'];

                // Coupon im Erfolgsfall vergessen
                if (isset($_SESSION['heidel_last_coupon'])) {
                    unset($_SESSION['heidel_last_coupon']);
                }
                // letzten Coupon im Erfolgsfall vergessen
                if (isset($_SESSION['cc_id'])) {
                    unset($_SESSION['cc_id']);
                }

                if ($payCode == 'dd') {
                    $repl = array(
                        '{ACC_IBAN}' => $res['all']['ACCOUNT_IBAN'],
                        /*'{ACC_BIC}'				=> $res['all']['ACCOUNT_BIC'],*/
                        '{ACC_IDENT}' => $res['all']['ACCOUNT_IDENTIFICATION'],
                        '{IDENT_CRED_ID}' => $res['all']['IDENTIFICATION_CREDITOR_ID'],
                        '<br>' => ''
                    );
                    $prePaidData = strtr(MODULE_PAYMENT_HPDD_SUCCESS, $repl);
                    $comment .= ' | Payment Info: ' . $prePaidData;
                }

                $status = constant('MODULE_PAYMENT_HP' . strtoupper($payCode) . '_PROCESSED_STATUS_ID');
                $this->addHistoryComment($insertId, $comment, $status);
                $this->saveIds($res['all']['IDENTIFICATION.UNIQUEID'], $insertId, 'hp' . $payCode,
                    $res['all']['IDENTIFICATION.SHORTID']);
                $this->setOrderStatus($insertId, $status);
                if ($payCode == 'dd') {
                    $values = array(
                        'kto' => $res['all']['ACCOUNT.NUMBER'],
                        'blz' => $res['all']['ACCOUNT.BANK'],
                        'own' => $holder
                    );
                }
            }
        } elseif (in_array($payCode, array(
                'pp',
                'iv'
            )) && $ACT_MOD_MODE == 'AFTER'
        ) {
            // Bei PP und IV keinen IFrame anzeigen
            if ($processingresult == "ACK" && $insertId > 0) {
                $comment = 'ShortID: ' . $res['all']['IDENTIFICATION.SHORTID'];
                $status = constant('MODULE_PAYMENT_HP' . strtoupper($payCode) . '_PROCESSED_STATUS_ID');
                $this->addHistoryComment($insertId, $comment, $status);
                $this->saveIds($res['all']['IDENTIFICATION.UNIQUEID'], $insertId, 'hp' . $payCode,
                    $res['all']['IDENTIFICATION.SHORTID']);
                $this->setOrderStatus($insertId, $status);
                $hpPayinfos = array(
                    'CONNECTOR_ACCOUNT_BANK' => $res['all']['CONNECTOR_ACCOUNT_BANK'],
                    'CONNECTOR_ACCOUNT_BIC' => $res['all']['CONNECTOR_ACCOUNT_BIC'],
                    'CONNECTOR_ACCOUNT_COUNTRY' => $res['all']['CONNECTOR_ACCOUNT_COUNTRY'],
                    'CONNECTOR_ACCOUNT_HOLDER' => $res['all']['CONNECTOR_ACCOUNT_HOLDER'],
                    'CONNECTOR_ACCOUNT_IBAN' => $res['all']['CONNECTOR_ACCOUNT_IBAN'],
                    'CONNECTOR_ACCOUNT_NUMBER' => $res['all']['CONNECTOR_ACCOUNT_NUMBER'],
                    'PRESENTATION_AMOUNT' => $res['all']['PRESENTATION_AMOUNT'],
                    'PRESENTATION_CURRENCY' => $res['all']['PRESENTATION_CURRENCY'],
                    'IDENTIFICATION_SHORTID' => $res['all']['IDENTIFICATION_SHORTID']
                );
                $repl = array(
                    '{AMOUNT}' => $hpPayinfos['PRESENTATION_AMOUNT'],
                    '{CURRENCY}' => $hpPayinfos['PRESENTATION_CURRENCY'],
                    '{ACC_COUNTRY}' => $hpPayinfos['CONNECTOR_ACCOUNT_COUNTRY'],
                    '{ACC_OWNER}' => $hpPayinfos['CONNECTOR_ACCOUNT_HOLDER'],
                    '{ACC_NUMBER}' => $hpPayinfos['CONNECTOR_ACCOUNT_NUMBER'],
                    '{ACC_BANKCODE}' => $hpPayinfos['CONNECTOR_ACCOUNT_BANK'],
                    '{ACC_BIC}' => $hpPayinfos['CONNECTOR_ACCOUNT_BIC'],
                    '{ACC_IBAN}' => $hpPayinfos['CONNECTOR_ACCOUNT_IBAN'],
                    '{SHORTID}' => $hpPayinfos['IDENTIFICATION_SHORTID']
                );

                if ($payCode == 'pp') {
                    $prePaidData = strtr(MODULE_PAYMENT_HPPP_SUCCESS, $repl);
                } else {
                    $prePaidData = strtr(MODULE_PAYMENT_HPIV_SUCCESS, $repl);
                }
                $comment = 'Payment Info: ' . $prePaidData . '<br>';
                $this->addHistoryComment($insertId, $comment, $status);
            }
            // BarPay
        } elseif (!empty($res['all']['CRITERION_BARPAY_PAYCODE_URL'])) {
            $comment = 'ShortID: ' . $res['all']['IDENTIFICATION.SHORTID'];
            $status = constant('MODULE_PAYMENT_HP' . strtoupper($payCode) . '_PROCESSED_STATUS_ID');
            $this->addHistoryComment($insertId, $comment, $status);
            $comment = 'Download Link: ' . $res['all']['CRITERION_BARPAY_PAYCODE_URL'];
            $this->addHistoryComment($insertId, $comment, $status);
            $this->saveIds($res['all']['IDENTIFICATION.UNIQUEID'], $insertId, 'hp' . $payCode,
                $res['all']['IDENTIFICATION.SHORTID']);
            $this->setOrderStatus($insertId, $status);
        } else {
            if ($this->actualPaymethod == 'TP' || $this->actualPaymethod == 'SU') {
                if (@constant('MODULE_PAYMENT_HP' . strtoupper($this->actualPaymethod)
                        . '_DIRECT_MODE') == 'LIGHTBOX'
                ) {
                    $hpIframe = '<center><div id="hpBox"><div style="background-color: #666; position:fixed;'
                        . ' display:block; margin:0; padding:0; top:0; left:0; opacity: 0.9; -moz-opacity: 0.9;'
                        . ' -khtml-opacity: 0.9; filter:alpha(opacity=90); z-index: 1000; width: 100%; height:'
                        . ' 100%;"></div>';
                    $hpIframe .= '<div style="z-index: 1001; position: absolute; width: 800px; top: 50%; left: 50%;'
                        . ' margin-top: -325px; margin-left: -400px;">';
                    $hpIframe .= '<iframe src="' . $src . '" frameborder="0" width="800" height="650" style="border:'
                        . ' 1px solid #ddd"></iframe><br>';
                    $hpIframe .= '<a href="" onClick="document.getElementById(\'hpBox\').style.display=\'none\';'
                        . ' return false;">close</a></div></div></center>';
                } else {
                    $hpIframe = '<center><iframe src="' . $src . '" frameborder="0" width="800"'
                        . ' height="650"></iframe></center>';
                }
            } else {
                $hpIframe = '<center><iframe src="' . $src
                    . '" frameborder="0" width="400" height="600"></iframe></center>';
            }
            $_SESSION['HEIDELPAY_IFRAME'] = $hpIframe;
            $this->trackStep('handleDebit', 'session', $_SESSION);
            // $_SESSION['hpLastPost'] = $_POST;
            if (!$debug) {
                header('Location: ' . $loc . 'heidelpay_checkout_iframe.php?' . session_name() . '=' . session_id());
            }
            if ($debug) {
                echo 'IFrame: ' . $hpIframe . '<br>';
            }
            exit();
        }
        if ($debug) {
            exit();
        }
        $this->trackStep('handleDebit', 'session', $_SESSION);
        $this->trackStep('handleDebit', 'hpIframe', $hpIframe);
        return $hpIframe;
    }

    /**
     * prepare post payload for api call
     *
     * @param $orderId string order reference id
     * @param $amount float order amount
     * @param $currency string order currency code
     * @param $payCode string current payment method
     * @param $userData array customer information
     * @param $lang string language code
     * @param string $mode     transaction mode
     * @param bool   $capture  booking mode
     * @param null   $uniqueId payment reference id
     *
     * @return mixed request payload
     */
    public function prepareData(
        $orderId,
        $amount,
        $currency,
        $payCode,
        $userData,
        $lang,
        $mode = 'DB',
        $capture = false,
        $uniqueId = null
    ) {
        $payCode = strtoupper($payCode);
        $amount = sprintf('%1.2f', $amount);
        $currency = strtoupper($currency);
        $userData = $this->encodeData($userData);

        $ACT_MOD_MODE = @constant('MODULE_PAYMENT_HP' . strtoupper($this->actualPaymethod) . '_MODULE_MODE');
        if (!in_array($ACT_MOD_MODE, array(
            'DIRECT',
            'AFTER',
            'NOWPF'
        ))
        ) {
            $ACT_MOD_MODE = 'AFTER';
        }

        $parameters['SECURITY.SENDER'] = constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_SECURITY_SENDER');
        $parameters['USER.LOGIN'] = constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_USER_LOGIN');
        $parameters['USER.PWD'] = constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_USER_PWD');
        $parameters['TRANSACTION.CHANNEL'] = constant('MODULE_PAYMENT_HP'
            . $this->actualPaymethod . '_TRANSACTION_CHANNEL');

        if (constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_TRANSACTION_MODE') == 'LIVE') {
            $txnMode = 'LIVE';
        } else {
            $txnMode = 'CONNECTOR_TEST';
        }
        $parameters['TRANSACTION.MODE'] = $txnMode;

        $parameters['REQUEST.VERSION'] = "1.0";
        $parameters['IDENTIFICATION.TRANSACTIONID'] = $orderId;
        $parameters['IDENTIFICATION.SHOPPERID'] = $_SESSION['customer_id'];
        if ($capture) {
            $parameters['FRONTEND.ENABLED'] = "false";
            if (!empty($uniqueId)) {
                $parameters['ACCOUNT.REGISTRATION'] = $uniqueId;
            }
        } else {
            $parameters['FRONTEND.ENABLED'] = "true";
        }
        $parameters['FRONTEND.REDIRECT_TIME'] = "0";
        $parameters['FRONTEND.POPUP'] = "false";
        $parameters['FRONTEND.MODE'] = "DEFAULT";
        $parameters['FRONTEND.LANGUAGE'] = $lang;
        $parameters['FRONTEND.LANGUAGE_SELECTOR'] = "true";
        $parameters['FRONTEND.ONEPAGE'] = "true";
        $parameters['FRONTEND.NEXTTARGET'] = "location.href";
        $parameters['FRONTEND.CSS_PATH'] = $this->pageURL . DIR_WS_CATALOG . "heidelpay_style.css";
        if ($mode == 'RG') {
            $parameters['FRONTEND.CSS_PATH'] = $this->pageURL . DIR_WS_CATALOG . "heidelpay_reg_style.css";
        }
        $parameters['FRONTEND.RETURN_ACCOUNT'] = "true";

        if ($this->actualPaymethod == 'SU') {
            $parameters['FRONTEND.ENABLED'] = "false";
        } elseif ($this->actualPaymethod == 'IDL' && $ACT_MOD_MODE == 'NOWPF') {
            $parameters['ACCOUNT.NUMBER'] = $_SESSION['hpIdealData']['otAccountNumber'];
            $parameters['ACCOUNT.BANK'] = $_SESSION['hpIdealData']['otBankCode'];
            $parameters['ACCOUNT.HOLDER'] = $_SESSION['hpIdealData']['onlineTransferHolder'];
            $parameters['ACCOUNT.COUNTRY'] = $_SESSION['hpIdealData']['onlineTransferCountry'];
            $parameters['ACCOUNT.BANKNAME'] = $_SESSION['hpIdealData']['onlineTransferInstitute'];
            $parameters['FRONTEND.ENABLED'] = "false";
        } elseif ($this->actualPaymethod == 'DD') {
            $parameters['ACCOUNT.HOLDER'] = $_SESSION['hpDDData']['Holder'];
            $parameters['ACCOUNT.IBAN'] = strtoupper($_SESSION['hpDDData']['AccountIBAN']);
            $parameters['FRONTEND.ENABLED'] = "false";
        } elseif ($this->actualPaymethod == 'GP') {
            $parameters['FRONTEND.ENABLED'] = "false";
        } elseif ($this->actualPaymethod == 'PPAL') {
            $parameters['ACCOUNT.BRAND'] = 'PAYPAL';
        } elseif ($this->actualPaymethod == 'BS') {
            $parameters['PAYMENT.CODE'] = "IV.PA";
            $parameters['ACCOUNT.BRAND'] = "BILLSAFE";
            $parameters['FRONTEND.ENABLED'] = "false";
            $oId = preg_replace('/.*Order /', '', $orderId);
            $order = $order = new order($oId);
            $bsParams = $this->getBillsafeBasket($order);
            $parameters = array_merge($parameters, $bsParams);
        } elseif ($this->actualPaymethod == 'BP') {
            $parameters['PAYMENT.CODE'] = "PP.PA";
            $parameters['ACCOUNT.BRAND'] = "BARPAY";
            $parameters['FRONTEND.ENABLED'] = "false";
        } elseif ($this->actualPaymethod == 'MK') {
            $parameters['PAYMENT.CODE'] = "PC.PA";
            $parameters['ACCOUNT.BRAND'] = "MANGIRKART";
            $parameters['FRONTEND.ENABLED'] = "false";
        }

        foreach ($this->availablePayments as $key => $value) {
            if ($value != $payCode) {
                $parameters["FRONTEND.PM." . ( string )($key + 1) . ".METHOD"] = $value;
                $parameters["FRONTEND.PM." . ( string )($key + 1) . ".ENABLED"] = "false";
            }
        }
        // Wenn der Payment Code noch nicht gesetzt wurde
        if (empty($parameters['PAYMENT.CODE'])) {
            $parameters['PAYMENT.CODE'] = $payCode . "." . $mode;
        }
        $parameters['FRONTEND.RESPONSE_URL'] = $this->pageURL . DIR_WS_CATALOG
            . "heidelpay_response.php" . '?' . session_name() . '=' . session_id();

        $parameters['NAME.GIVEN'] = trim($userData['firstname']);
        $parameters['NAME.FAMILY'] = trim($userData['lastname']);
        $parameters['NAME.SALUTATION'] = $userData['salutation'];
        $parameters['NAME.COMPANY'] = $userData['company'];
        $parameters['ADDRESS.STREET'] = $userData['street'];
        $parameters['ADDRESS.ZIP'] = $userData['zip'];
        $parameters['ADDRESS.CITY'] = $userData['city'];
        $parameters['ADDRESS.COUNTRY'] = $userData['country'];
        $parameters['ADDRESS.STATE'] = $userData['state'];
        $parameters['CONTACT.EMAIL'] = $userData['email'];
        $parameters['CONTACT.IP'] = $userData['ip'];
        $parameters['PRESENTATION.AMOUNT'] = $amount;
        $parameters['PRESENTATION.CURRENCY'] = $currency;
        $parameters['ACCOUNT.COUNTRY'] = $userData['country'];

        $parameters['FRONTEND.BUTTON.1.NAME'] = 'PAY';
        $parameters['FRONTEND.BUTTON.1.TYPE'] = 'BUTTON';
        $parameters['FRONTEND.BUTTON.1.LABEL'] = constant('MODULE_PAYMENT_HP'
            . $this->actualPaymethod . '_FRONTEND_BUTTON_CONTINUE');
        $parameters['FRONTEND.BUTTON.2.NAME'] = 'CANCEL';
        $parameters['FRONTEND.BUTTON.2.TYPE'] = 'BUTTON';
        $parameters['FRONTEND.BUTTON.2.LABEL'] = constant('MODULE_PAYMENT_HP'
            . $this->actualPaymethod . '_FRONTEND_BUTTON_CANCEL');

        $parameters['SHOP.TYPE'] = "XTC 3.4";
        $parameters['SHOPMODULE.VERSION'] = "Premium " . $this->version;
        return $parameters;
    }

    /**
     * Basket details for Billsafe api
     *
     * @param $order order object
     *
     * @return mixed request parrameter
     */
    public function getBillsafeBasket($order)
    {
        global $xtPrice;
        $order->cart();

        $items = $order->products;
        $i = 0;
        if ($items) {
            foreach ($items as $id => $item) {
                $i++;
                $prefix = 'CRITERION.POS_' . sprintf('%02d', $i);
                $parameters[$prefix . '.POSITION'] = $i;
                $parameters[$prefix . '.QUANTITY'] = ( int )$item['qty'];
                $parameters[$prefix . '.UNIT'] = 'Stk.'; // Liter oder so
                if ($_SESSION['customers_status']['customers_status_show_price_tax'] == '0') {
                    $parameters[$prefix . '.AMOUNT_UNIT'] = round($item['price'] * 100);
                    $parameters[$prefix . '.AMOUNT'] = round($item['final_price'] * 100);
                } else {
                    $parameters[$prefix . '.AMOUNT_UNIT_GROSS'] = round($item['price'] * 100);
                    $parameters[$prefix . '.AMOUNT_GROSS'] = round($item['price'] * 100);
                }
                $parameters[$prefix . '.TEXT'] = $item['name'];
                $parameters[$prefix . '.ARTICLE_NUMBER'] = $item['id'];
                $parameters[$prefix . '.PERCENT_VAT'] = sprintf('%1.2f', $item['tax']);
                $parameters[$prefix . '.ARTICLE_TYPE'] = 'goods';
            }
        }
        if ($order->info['shipping_cost'] > 0) {
            $shipping_id = explode('_', $order->info['shipping_class']);
            $shipping_id = $shipping_id[0];
            $shipping_tax_rate = $this->get_shipping_tax_rate($shipping_id);
            $i++;
            $prefix = 'CRITERION.POS_' . sprintf('%02d', $i);
            $parameters[$prefix . '.POSITION'] = $i;
            $parameters[$prefix . '.QUANTITY'] = '1';
            $parameters[$prefix . '.UNIT'] = 'Stk.'; // Liter oder so
            if ($_SESSION['customers_status']['customers_status_show_price_tax'] == '0') {
                $parameters[$prefix . '.AMOUNT_UNIT'] = round($order->info['shipping_cost'] * 100);
                $parameters[$prefix . '.AMOUNT'] = round($order->info['shipping_cost'] * 100);
            } else {
                $parameters[$prefix . '.AMOUNT_UNIT_GROSS'] = round($order->info['shipping_cost']
                        * 100) + round($order->info['shipping_cost'] * $item['tax']);
                $parameters[$prefix . '.AMOUNT_GROSS'] = round($order->info['shipping_cost']
                        * 100) + round($order->info['shipping_cost'] * $item['tax']);
            }
            $parameters[$prefix . '.TEXT'] = $order->info['shipping_method'];
            $parameters[$prefix . '.ARTICLE_NUMBER'] = '0';
            $parameters[$prefix . '.PERCENT_VAT'] = sprintf('%1.2f', $shipping_tax_rate);
            $parameters[$prefix . '.ARTICLE_TYPE'] = 'shipment';
        }
        $items = $order->totals;
        if ($items) {
            foreach ($items as $id => $item) {
                if ($item['value'] >= 0) {
                    continue;
                }
                $i++;
                $prefix = 'CRITERION.POS_' . sprintf('%02d', $i);
                $parameters[$prefix . '.POSITION'] = $i;
                $parameters[$prefix . '.QUANTITY'] = 1;
                $parameters[$prefix . '.UNIT'] = 'Stk.'; // Einheit
                if ($_SESSION['customers_status']['customers_status_show_price_tax'] == '0') {
                    $parameters[$prefix . '.AMOUNT_UNIT'] = round($item['value'] * 100);
                    $parameters[$prefix . '.AMOUNT'] = round($item['value'] * 100);
                } else {
                    $parameters[$prefix . '.AMOUNT_UNIT'] = round($item['value']
                            * 100) + round($item['value'] * $item['tax']);
                    $parameters[$prefix . '.AMOUNT'] = round($item['value']
                            * 100) + round($item['value'] * $item['tax']);
                }
                $parameters[$prefix . '.TEXT'] = $item['title'];
                $parameters[$prefix . '.ARTICLE_NUMBER'] = '0';
                $parameters[$prefix . '.PERCENT_VAT'] = sprintf('%1.2f', 0);
                $parameters[$prefix . '.ARTICLE_TYPE'] = 'voucher';
            }
        }

        return $parameters;
    }

    /**
     * calculate shipping tax
     *
     * @param $shipping_id integer id of the used shipment
     *
     * @return float|int tax rate
     */
    public function get_shipping_tax_rate($shipping_id)
    {
        $check_query = xtc_db_query(
            'SELECT configuration_value FROM ' . TABLE_CONFIGURATION
            . ' WHERE configuration_key = "MODULE_SHIPPING_' . $shipping_id . '_TAX_CLASS"'
        );
        $configuration = xtc_db_fetch_array($check_query);
        $tax_class_id = $configuration['configuration_value'];
        $shipping_tax_rate = xtc_get_tax_rate($tax_class_id);
        return $shipping_tax_rate;
    }

    /**
     * encode request to utf8
     *
     * @param $data array request payload
     *
     * @return array utf8 request payload
     */
    public function encodeData($data)
    {
        $tmp = array();
        foreach ($data as $k => $v) {
            $tmp[$k] = $v;
            if (!$this->isUTF8($v)) {
                $tmp[$k] = utf8_encode($v);
            }
        }
        return $tmp;
    }

    /**
     * test character set
     *
     * @param $string string single parameter to test
     *
     * @return bool return tru if encoding is utf8
     */
    public function isUTF8($string)
    {
        if (is_array($string)) {
            $enc = implode('', $string);
            return @!((ord($enc[0]) != 239) && (ord($enc[1]) != 187) && (ord($enc[2]) != 191));
        } else {
            return (utf8_encode(utf8_decode($string)) == $string);
        }
    }

    /**
     * is https available
     *
     * @return bool
     */
    public function isHTTPS()
    {
        if (strpos($_SERVER['HTTP_HOST'], '.local') === false) {
            if (!isset($_SERVER['HTTPS']) || (strtolower($_SERVER['HTTPS']) != 'on' && $_SERVER['HTTPS'] != '1')) {
                return false;
            }
        } else {
            // Local
            return false;
        }
        return true;
    }

    /**
     * Send request to heidelpay api
     *
     * @param $data
     * @param null $xml
     *
     * @return mixed|string
     */
    public function doRequest($data, $xml = null)
    {
        $url = $this->demo_url_new;
        $result = array();
        if (!empty($xml)) {
            $url = 'https://test-heidelpay.hpcgw.net/TransactionCore/xml';
        } // XML
        if (constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_TRANSACTION_MODE') == 'LIVE') {
            $url = $this->live_url_new;
            if (!empty($xml)) {
                $url = 'https://heidelpay.hpcgw.net/TransactionCore/xml';
            } // XML
        }
        $this->url = $url;

        foreach (array_keys($data) as $key) {
            $data[$key] = utf8_decode($data[$key]);
            $$key .= $data[$key];
            $$key = urlencode($$key);
            $$key .= "&";
            $var = strtoupper($key);
            $value = $$key;
            $result .= "$var=$value";
        }
        $strPOST = stripslashes($result);
        if (!empty($xml)) {
            $strPOST = 'load=' . urlencode($xml);
        }

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $strPOST);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, "Heidelpay Request");

            $this->response = curl_exec($ch);
            $this->error = curl_error($ch);
            curl_close($ch);

            $res = $this->response;
            if (!$this->response && $this->error) {
                $res = 'PROCESSING.RESULT=NOK&PROCESSING.RETURN=' . $this->error;
            }
        } else {
            $msg = urlencode('Curl Fehler');
            $res = 'PROCESSING.RESULT=NOK&PROCESSING.RETURN=' . $msg;
        }

        return $res;
    }

    /**
     * parse post string and return an array
     *
     * @param $curlResult array post result as as a string
     *
     * @return array array result
     */
    public function parseResult($curlResult)
    {
        $r_arr = explode("&", $curlResult);
        foreach ($r_arr as $buf) {
            $temp = urldecode($buf);
            list($postatt, $postvar) = explode('=', $temp, 2);
            $returnvalue[$postatt] = $postvar;
        }
        $processingresult = $returnvalue['PROCESSING.RESULT'];
        if (empty($processingresult)) {
            $processingresult = $returnvalue['POST.VALIDATION'];
        }
        $redirectURL = $returnvalue['FRONTEND.REDIRECT_URL'];
        if (!isset($returnvalue['PROCESSING.RETURN']) && $returnvalue['POST.VALIDATION'] > 0) {
            $returnvalue['PROCESSING.RETURN'] = 'Errorcode: ' . $returnvalue['POST.VALIDATION'];
        }
        ksort($returnvalue);
        return array(
            'result' => $processingresult,
            'url' => $redirectURL,
            'all' => $returnvalue
        );
    }

    /**
     *
     * @param $dateFrom
     * @param $dateUntil
     * @param array $types
     * @param array $identification
     * @param array $methods
     *
     * @return string
     */
    public function getQueryXML(
        $dateFrom,
        $dateUntil,
        $types = array('RC'),
        $identification = array(),
        $methods = array()
    ) {
        $parameters = array();
        $parameters['SECURITY.SENDER'] = constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_SECURITY_SENDER');
        $parameters['USER.LOGIN'] = constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_USER_LOGIN');
        $parameters['USER.PWD'] = constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_USER_PWD');
        $parameters['TRANSACTION.CHANNEL'] = constant('MODULE_PAYMENT_HP' . $this->actualPaymethod
            . '_TRANSACTION_CHANNEL');
        $parameters['TRANSACTION.MODE'] = constant('MODULE_PAYMENT_HP' . $this->actualPaymethod . '_TRANSACTION_MODE');

        $type = 'STANDARD';
        if (!empty($identification)) {
            $type = 'LINKED_TRANSACTIONS';
        }

        $xml = '<Request version="1.0">
      <Header><Security sender="' . $parameters['SECURITY.SENDER'] . '"/></Header>
      <Query entity="' . $parameters['TRANSACTION.CHANNEL'] . '" level="CHANNEL" mode="'
            . $parameters['TRANSACTION.MODE'] . '" type="' . $type . '">
      <User login="' . $parameters['USER.LOGIN'] . '" pwd="' . $parameters['USER.PWD'] . '"/>
      <Period from="' . $dateFrom . '" to="' . $dateUntil . '"/>';

        if (!empty($identification)) {
            $xml .= '<Identification>';
            if (!empty($identification['uniqueIDs'])) {
                $xml .= '<UniqueIDs>';
                foreach ($identification['uniqueIDs'] as $k => $v) {
                    $xml .= '<ID>' . $v . '</ID>';
                }
                $xml .= '</UniqueIDs>';
            }
            if (!empty($identification['uniqueID'])) {
                $xml .= '<UniqueID>' . $identification['uniqueID'] . '</UniqueID>';
            }
            if (!empty($identification['shortID'])) {
                $xml .= '<ShortID>' . $identification['shortID'] . '</ShortID>';
            }
            if (!empty($identification['transactionID'])) {
                $xml .= '<TransactionID>' . $identification['transactionID'] . '</TransactionID>';
            }
            $xml .= '</Identification>';
        }

        if (!empty($methods)) {
            $xml .= '<Methods>';
            foreach ($methods as $k => $v) {
                $xml .= '<Method code="' . $v . '"/>';
            }
            $xml .= '</Methods>';
        }

        if (!empty($types)) {
            $xml .= '<Types>';
            foreach ($types as $k => $v) {
                $xml .= '<Type code="' . $v . '"/>';
            }
            $xml .= '</Types>';
        }

        $xml .= '</Query></Request>';
        return $xml;
    }

    public function addHistoryComment($order_id, $comment, $status = '', $customer_notified = '0')
    {
        if (empty($order_id) || empty($comment)) {
            return false;
        }
        // Alten Eintrag laden
        $orderHistory = $this->getLastHistoryComment($order_id);
        // Kunde benachrichtigt
        $orderHistory['customer_notified'] = $customer_notified;
        // Timestamp korrekt erneuern
        $orderHistory['date_added'] = date('Y-m-d H:i:s');
        // Kommentar setzen
        $orderHistory['comments'] = urldecode($comment);
        // Neuer Status eintragen
        if (!empty($status)) {
            $orderHistory['orders_status_id'] = addslashes($status);
        }
        // Alte History ID entfernen
        unset($orderHistory['orders_status_history_id']);
        // Neue History eintragen
        return xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, $orderHistory);
    }

    public function getHistoryComment($order_id, $search)
    {
        if (empty($order_id) || empty($search)) {
            return false;
        }
        $sql = 'SELECT * FROM `' . TABLE_ORDERS_STATUS_HISTORY . '`
      WHERE `orders_id`       = "' . addslashes($order_id) . '"
      AND `comments` LIKE "%' . addslashes($search) . '%"
      ';
        $orderHistoryArray = xtc_db_query($sql);
        $ordersHistory = xtc_db_fetch_array($orderHistoryArray);
        return $ordersHistory['comments'];
    }

    public function hasHistoryComment($order_id, $status, $customer_notified)
    {
        if (empty($order_id) || empty($status) || empty($customer_notified)) {
            return false;
        }
        $sql = 'SELECT * FROM `' . TABLE_ORDERS_STATUS_HISTORY . '`
      WHERE `orders_id`       = "' . addslashes($order_id) . '"
      AND `orders_status_id`  = "' . addslashes($status) . '"
      AND `customer_notified` = "' . addslashes($customer_notified) . '"
      ';
        $orderHistoryArray = xtc_db_query($sql);
        while ($ordersHistoryTMP = xtc_db_fetch_array($orderHistoryArray)) {
            $ordersHistory[] = $ordersHistoryTMP;
        }
        return count($ordersHistory) > 0;
    }

    public function getLastHistoryComment($order_id)
    {
        if (empty($order_id)) {
            return array();
        }
        $sql = 'SELECT * FROM `' . TABLE_ORDERS_STATUS_HISTORY . '`
      WHERE `orders_id` = "' . addslashes($order_id) . '"
      ORDER BY `orders_status_history_id` DESC
      ';
        $orderHistoryArray = xtc_db_query($sql);
        return xtc_db_fetch_array($orderHistoryArray);
    }

    public function getOrderHistory($order_id)
    {
        if (empty($order_id)) {
            return array();
        }
        $sql = 'SELECT * FROM `' . TABLE_ORDERS_STATUS_HISTORY . '`
      WHERE `orders_id` = "' . addslashes($order_id) . '"
      ORDER BY `orders_status_history_id` DESC
      ';
        $orderHistoryArray = xtc_db_query($sql);
        $ordersHistory = array();
        while ($ordersHistoryTMP = xtc_db_fetch_array($orderHistoryArray)) {
            $ordersHistory[] = $ordersHistoryTMP;
        }
        return $ordersHistory;
    }

    public function setOrderStatus($order_id, $status, $doubleCheck = false)
    {
        global $db_link;
        // Status History laden
        $orderHistory = $this->getOrderHistory($order_id);
        if ($doubleCheck) {
            // Pr�fen ob Status schon mal gesetzt
            $found = false;
            foreach ($orderHistory as $k => $v) {
                if ($v['orders_status_id'] == $status) {
                    $found = true;
                }
            }
            // Wenn Status schon mal gesetzt dann nichts tun
            if ($found) {
                return false;
            }
        }
        // Bestellstatus setzen
        $res = xtc_db_query("UPDATE `" . TABLE_ORDERS . "` SET `orders_status` = '"
            . addslashes($status) . "' WHERE `orders_id` = '" . addslashes($order_id) . "'");
        $stat = mysqli_affected_rows($db_link);
        return $stat > 0;
    }

    public function saveIds($uniqueId, $order_id, $paymeth, $shortId)
    {
        $create = 'CREATE TABLE IF NOT EXISTS `heidelpay_transaction_data` ('
            . '`uniqueID` varchar(32) COLLATE latin1_german1_ci NOT NULL,'
            . '`orderId` int(11) NOT NULL,'
            . '`paymentmethod` varchar(6) COLLATE latin1_german1_ci NOT NULL,'
            . '`shortID` varchar(14) COLLATE latin1_german1_ci NOT NULL,'
            . 'PRIMARY KEY (`uniqueID`),'
            . 'KEY `orderId` (`orderId`))';

        $insert = 'INSERT INTO `heidelpay_transaction_data` SET `uniqueID`="'
            . addslashes($uniqueId) . '", `orderId`="' . addslashes($order_id) . '", `paymentmethod`="'
            . addslashes($paymeth) . '", `shortID`="' . addslashes($shortId) . '"';

        xtc_db_query($create);

        return xtc_db_query($insert);
    }

    public function saveOrderComment($order_id, $comment)
    {
        return xtc_db_query("UPDATE `" . TABLE_ORDERS . "` SET `comments` = '"
            . addslashes($comment) . "' WHERE `orders_id` = '" . addslashes($order_id) . "'");
    }

    public function install_db($tablename, $fieldname, $add_fieldspec, $setindex = false)
    {
        $q = xtc_db_query('SHOW COLUMNS FROM ' . $tablename);
        while ($values = xtc_db_fetch_array($q)) {
            if ($values['Field'] == $fieldname) {
                return true;
            }
        }
        xtc_db_query($add_fieldspec);
        if ($setindex) {
            xtc_db_query('ALTER TABLE `' . $tablename . '` ADD INDEX (`' . $fieldname . '`)');
        }
        $q = xtc_db_query('SHOW COLUMNS FROM ' . $tablename);
        while ($values = xtc_db_fetch_array($q)) {
            if ($values['Field'] == $fieldname) {
                return true;
            }
        }
        return false;
    }

    public function saveMEMO($customerId, $key, $value)
    {
        $data = $this->loadMEMO($customerId, $key);
        if (!empty($data)) {
            return xtc_db_query('UPDATE `customers_memo` SET `memo_text` = "'
                . addslashes($value) . '", `memo_date` = NOW(), `poster_id` = 1 WHERE `customers_id` = "'
                . addslashes($customerId) . '" AND `memo_title` = "' . addslashes($key) . '"');
        } else {
            return xtc_db_query('INSERT INTO `customers_memo` SET `memo_text` = "'
                . addslashes($value) . '", `customers_id` = "' . addslashes($customerId) . '", `memo_title` = "'
                . addslashes($key) . '", `memo_date` = NOW(), `poster_id` = 1');
        }
    }

    public function loadMEMO($customerId, $key)
    {
        $res = xtc_db_query('SELECT * FROM `customers_memo` WHERE `customers_id` = "'
            . addslashes($customerId) . '" AND `memo_title` = "' . addslashes($key) . '"');
        $res = xtc_db_fetch_array($res);
        return $res['memo_text'];
    }

    public function getPayment($orderId)
    {
        $sql = 'SELECT `payment_class` FROM `' . TABLE_ORDERS . '` WHERE `orders_id` = "' . ( int )$orderId . '" ';
        $res = xtc_db_query($sql);
        $res = xtc_db_fetch_array($res);
        return $res['payment_class'];
    }

    public function getOpenOrdersDate($paymentClass, $payStatus)
    {
        $this->actualPaymethod = strtoupper(substr($paymentClass, 2, 2));
        $sql = 'SELECT min(date(`date_purchased`)) AS `min`, max(date(`date_purchased`)) AS `max` FROM `'
            . TABLE_ORDERS . '` WHERE `payment_class` = "' . $paymentClass . '" AND `orders_status` = "'
            . $payStatus . '" ';
        $res = xtc_db_query($sql);
        return xtc_db_fetch_array($res);
    }

    public function getOpenOrders($payment_class, $paystatus)
    {
        $sql = 'SELECT * FROM `' . TABLE_ORDERS . '` WHERE `payment_class` = "' . $payment_class
            . '" AND `orders_status` = "' . $paystatus . '" ';

        $res = xtc_db_query($sql);
        $tmp = array();
        while ($row = xtc_db_fetch_array($res)) {
            $tmp[$row['orders_id']] = $row;
        }
        return $tmp;
    }

    public function getOpenOrderByUniqueId($uniqueId, $payment_class)
    {
        $sql = 'SELECT * FROM `orders` JOIN `heidelpay_transaction_data`'
            . ' ON orders.orders_id = heidelpay_transaction_data.orderID'
            . ' JOIN  `orders_total` ON heidelpay_transaction_data.orderID=orders_total.orders_id'
            . 'WHERE heidelpay_transaction_data.uniqueID = "' . $uniqueId . '"'
            . 'AND orders.payment_class= "' . $payment_class . '"'
            . 'AND orders_total.class = "ot_total"';

        $res = xtc_db_query($sql);
        return xtc_db_fetch_array($res);
    }

    public function getLangId($code)
    {
        $sql = 'SELECT `languages_id` FROM `' . TABLE_LANGUAGES . '` WHERE `code` = "' . addslashes($code) . '" ';
        // echo $sql;
        $res = xtc_db_query($sql);
        $res = xtc_db_fetch_array($res);
        return $res['languages_id'];
    }

    public function getOrderStatusName($statusId)
    {
        $langId = $this->getLangId('de');
        if ($langId <= 0) {
            $langId = $this->getLangId('en');
        }
        $sql = 'SELECT `orders_status_name` FROM `' . TABLE_ORDERS_STATUS
            . '` WHERE `orders_status_id` = "' . ( int )$statusId . '" AND `language_id` = "' . ( int )$langId . '" ';
        $res = xtc_db_query($sql);
        $res = xtc_db_fetch_array($res);
        return $res['orders_status_name'];
    }

    public function checkOrderStatusHistory($orderId, $shortId)
    {
        $sql = 'SELECT * FROM `' . TABLE_ORDERS_STATUS_HISTORY
            . '` WHERE `orders_id` = "' . ( int )$orderId . '" AND `comments` LIKE "%' . $shortId . '%" ';
        $res = xtc_db_query($sql);
        $res = xtc_db_fetch_array($res);
        return !empty($res);
    }

    public function getPayCodeByChannel($TRANSACTION_CHANNEL)
    {
        $otPayTypes = array(
            'gp',
            'su',
            'tp',
            'idl',
            'eps'
        );
        $keys = array();
        foreach ($otPayTypes as $k => $v) {
            $keys[] = 'MODULE_PAYMENT_HP' . strtoupper($v) . '_TRANSACTION_CHANNEL';
        }
        $sql = 'SELECT * FROM `configuration` WHERE `configuration_value` = "'
            . addslashes($TRANSACTION_CHANNEL) . '" AND `configuration_key` IN ("' . implode('","', $keys) . '") ';
        // echo $sql;
        $res = xtc_db_query($sql);
        $res = xtc_db_fetch_array($res);
        return str_replace(array(
            'MODULE_PAYMENT_HP',
            '_TRANSACTION_CHANNEL'
        ), '', $res['configuration_key']);
    }

    public function getCustomerState($state)
    {
        $customer_state = xtc_db_query('SELECT `zone_code` FROM `'
            . TABLE_ZONES . '` WHERE `zone_name` = "' . $state . '" OR `zone_code` = "' . $state . '"');
        $attributes_values = xtc_db_fetch_array($customer_state);
        $cus_state = $attributes_values['zone_code'];
        return $cus_state;
    }

    public function getCustomerStateByZoneId($zoneId)
    {
        $customer_state = xtc_db_query('SELECT `zone_code` FROM `'
            . TABLE_ZONES . '` WHERE `zone_id` = "' . $zoneId . '"');
        $attributes_values = xtc_db_fetch_array($customer_state);
        $cus_state = $attributes_values['zone_code'];
        return $cus_state;
    }

    public function getCustomerCountry($country)
    {
        $country_query = xtc_db_query('SELECT `countries_iso_code_2` FROM `'
            . TABLE_COUNTRIES . '` WHERE `countries_id` = "' . $country . '"');
        $country_res = xtc_db_fetch_array($country_query);
        $cus_country = $country_res['countries_iso_code_2'];
        return $cus_country;
    }

    public function rememberOrderData($order)
    {
        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 &&
            $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1
        ) {
            $total = $order->info['total'] + $order->info['tax'];
        } else {
            $total = $order->info['total'];
        }

        $amount = $total;
        $user_id = $order->customer['csID']; //customer id
        $currency = $order->info['currency'];
        $language = strtoupper($_SESSION['language_code']);

        $userData = array(
            'company' => $order->customer['company'],
            'firstname' => $order->billing['firstname'],
            'lastname' => $order->billing['lastname'],
            'salutation' => ($order->customer['gender'] == 'f' ? 'MRS' : 'MR'),
            'street' => $order->billing['street_address'],
            'zip' => $order->billing['postcode'],
            'city' => $order->billing['city'],
            'country' => $order->billing['country']['iso_code_2'],
            'email' => $order->customer['email_address'],
            'state' => $this->getCustomerStateByZoneId($order->billing['zone_id']),
            'ip' => $_SERVER['REMOTE_ADDR']
        );
        if (empty($userData['state'])) {
            $userData['state'] = $userData['country'];
        }
        $_SESSION['hpLastData']['user_id'] = $user_id;
        $_SESSION['hpLastData']['amount'] = $amount;
        $_SESSION['hpLastData']['currency'] = $currency;
        $_SESSION['hpLastData']['language'] = $language;
        $_SESSION['hpLastData']['userData'] = $userData;

        $this->trackStep('process_button', 'order', $order);
        $this->trackStep('process_button', 'session', $_SESSION);
    }

    public function trackStep($point, $var, $val)
    {
        if (!empty($this->hpdebug)) {
            $tmp['hpTracking'][$point][$var] = $val;
            $filename = DIR_FS_CATALOG . 'cache/customer_' . $_SESSION['customer_id'] . '.log';
            if ($handle = fopen($filename, 'a')) {
                fwrite($handle, date('Y.m.d H:i:s') . "\n" . print_r($tmp['hpTracking'], 1));
                fclose($handle);
            }
        }
    }

    public function saveSteps($filename)
    {
        if (!empty($this->hpdebug)) {
            $path = DIR_FS_CATALOG . 'cache/';
            $filename_old = 'customer_' . $_SESSION['customer_id'] . '.log';
            rename($path . $filename_old, $path . $filename);
        }
    }

    public function setConf($key, $value)
    {
        global $db_link;
        $sql = 'UPDATE `' . TABLE_CONFIGURATION . '` SET `configuration_value` = "' . addslashes($value)
            . '" WHERE `configuration_key` = "MODULE_PAYMENT_HP' . addslashes(strtoupper($key)) . '" ';
        $res = xtc_db_query($sql);
        return mysqli_affected_rows($db_link);
    }

    public function deleteCoupon($order_id)
    {
        return xtc_db_query("DELETE FROM `" . TABLE_COUPON_REDEEM_TRACK . "` WHERE `order_id` = '"
            . addslashes($order_id) . "'");
    }
}

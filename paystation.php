<?php

defined('_JEXEC') or die('Restricted access');
?><?php

class plgHikashoppaymentPaystation extends hikashopPaymentPlugin {

    var $accepted_currencies = array(
        'GBP', 'USD', 'EUR', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT',
        'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BTN', 'BWP', 'BYR', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP',
        'CNY', 'COP', 'CRC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'FJD', 'FKP', 'GEL',
        'GHS', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'IQD', 'IRR',
        'ISK', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR',
        'LTL', 'LVL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MWK', 'MXN', 'MYR', 'MZN',
        'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON',
        'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SVC', 'SYP', 'SZL',
        'THB', 'TJS', 'TMT', 'TND', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VEF', 'VND', 'WST', 'XAF',
        'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMK', 'ZWL'
    );
    
    var $multiple = true;
    var $name = 'paystation';
    var $pluginConfig = array(
        'paystation_id' => array('Paystation ID', 'input'),
        'gateway_id' => array('Gateway', 'input'),
        'HMACkey' => array('HMAC key', 'input'),
        'testmode' => array('Transaction Mode', 'list', array(
                'TEST' => 'Test',
                'LIVE' => 'Live'
            )),
        'postback' => array('Postback', 'list', array(
                'ENABLED' => 'Enabled',
                'DISABLED' => 'Disabled'
            )),
        'invalid_status' => array('Unsuccessful payment status', 'orderstatus'),
        'verified_status' => array('Successful payment status', 'orderstatus')
    );

    function makePaystationSessionID($min = 8, $max = 8) {
        // seed the random number generator - straight from PHP manual
        $seed = (double) time() * getrandmax();
        srand($seed);

        // make a string of $max characters with ASCII values of 40-122
        $p = 0;
        $pass = "";
        while ($p < $max):
            $r = chr(123 - (rand() % 75));

            // get rid of all non-alphanumeric characters
            if (!($r >= 'a' && $r <= 'z') && !($r >= 'A' && $r <= 'Z') && !($r >= '1' && $r <= '9'))
                continue;
            $pass.=$r;

            $p++;
        endwhile;
        // if string is too short, remake it
        if (strlen($pass) < $min):
            $pass = $this->makePaystationSessionID($min, $max);
        endif;

        return $pass;
    }

    function directTransaction($url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if (curl_error($ch)) {
            echo curl_error($ch);
        }
        curl_close($ch);

        return $result;
    }

    function onAfterOrderConfirm(&$order, &$methods, $method_id) {

        parent::onAfterOrderConfirm($order, $methods, $method_id);

        $viewType = 'end';

        $server_url = HIKASHOP_LIVE . 'index.php';

        $returnURL = $server_url . '?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=paystation&notif_id=' . $method_id . '&tmpl=component&lang=' . $this->locale . $this->url_itemid;
        $amount = round($order->cart->full_total->prices[0]->price_value_with_tax, (int) $this->currency->currency_locale['int_frac_digits']) * 100;

        $returnURL = urlencode($returnURL);
        $postbackURL = $returnURL;
        // Attempt to initiate a transaction with PayStation and get the redirect URL
        $email = $order->cart->customer->email;
        $reservedOrderId = $order->order_id;

        $authenticationKey = trim($this->payment_params->HMACkey);
        $hmacWebserviceName = 'paystation';
        $pstn_HMACTimestamp = time();

        $paystationURL = "https://www.paystation.co.nz/direct/paystation.dll";

        $testMode = ($this->payment_params->testmode == "TEST");
        $postback = ($this->payment_params->postback == "ENABLED");
        $pstn_pi = trim($this->payment_params->paystation_id);
        $pstn_gi = trim($this->payment_params->gateway_id);
        $site = ''; // site can be used to differentiate transactions from different websites in admin.
        if ($testMode)
            $pstn_mr = urlencode($email . ':test-mode:' . $reservedOrderId);
        else
            $pstn_mr = urlencode($email . ':' . $reservedOrderId);

        $merchantSession = urlencode($reservedOrderId . '-' . ((float) microtime(true)) . '-' . $this->makePaystationSessionID(18, 18)); // max length of ms is 64 char 

        $paystationParams = "paystation&pstn_pi=" . $pstn_pi . "&pstn_gi=" . $pstn_gi . "&pstn_ms=" . $merchantSession . "&pstn_am=" . $amount . "&pstn_mr=" . $pstn_mr . "&pstn_nr=t";
        $paystationParams .= "&pstn_du=" . $returnURL;

        if ($postback == '1')
            $paystationParams .= "&pstn_dp=" . $postbackURL;

        if ($testMode == true) {
            $paystationParams = $paystationParams . "&pstn_tm=t";
        }

        $hmacBody = pack('a*', $pstn_HMACTimestamp) . pack('a*', $hmacWebserviceName) . pack('a*', $paystationParams);
        $hmacHash = hash_hmac('sha512', $hmacBody, $authenticationKey);
        $hmacGetParams = '?pstn_HMACTimestamp=' . $pstn_HMACTimestamp . '&pstn_HMAC=' . $hmacHash;
        $paystationURL.= $hmacGetParams;

        $initiationResult = $this->directTransaction($paystationURL, $paystationParams);
        preg_match_all("/<(.*?)>(.*?)\</", $initiationResult, $outarr, PREG_SET_ORDER);
        $n = 0;
        while (isset($outarr[$n])) {
            $retarr[$outarr[$n][1]] = strip_tags($outarr[$n][0]);
            $n++;
        }

        $_SESSION['redirectURL'] = '';
        if (isset($retarr['DigitalOrder']) && isset($retarr['PaystationTransactionID'])) {
            header("Location: " . $retarr['DigitalOrder']);
            exit();
        } else {
            exit("Paystation initiation failed with paraters: <br/>$paystationParams");
            return null;
        }
    }

    function onPaymentNotification(&$statuses) {
        $xml = file_get_contents('php://input');
        $xml = simplexml_load_string($xml);
        if (!empty($xml))
            $this->postback($xml, $statuses);
        else
            $this->returnURL($statuses);
    }

    private function postback($xml, &$statuses) {
        $this->app = JFactory::getApplication();
        $this->url_itemid = empty($Itemid) ? '' : '&Itemid=' . $Itemid;

        $method_id = JRequest::getInt('notif_id', 0);
        $this->pluginParams($method_id);
        $this->payment_params = & $this->plugin_params;
        $testmode = ($this->payment_params->testmode == "TEST");
        $postback = ($this->payment_params->postback == "ENABLED");
        $pstn_pi = trim($this->payment_params->paystation_id);
        $pstn_gi = trim($this->payment_params->gateway_id);

        if (!$postback)
            exit("Postback not enabled in Hikashop payment module.");

        if (!empty($xml)) {
            $errorCode = (int) $xml->ec;
            $errorMessage = $xml->em;
            $transactionId= $xml->ti;
            $cardType = $xml->ct;
            $merchantReference = $xml->merchant_ref;
            $testMode = $xml->tm;
            $merchantSession = $xml->MerchantSession;
            $usedAcquirerMerchantId = $xml->UsedAcquirerMerchantID;
            $amount = $xml->PurchaseAmount; // Note this is in cents
            $transactionTime = $xml->TransactionTime;
            $requestIp = $xml->RequestIP;

            $message = "Error Code: " . $errorCode . "<br/>";
            $message .= "Error Message: " . $errorMessage . "<br/>";
            $message .= "Transaction ID: " . $transactionId . "<br/>";
            $message .= "Card Type: " . $cardType . "<br/>";
            $message .= "Merchant Reference: " . $merchantReference . "<br/>";
            $message .= "Test Mode: " . $testMode . "<br/>";
            $message .= "Merchant Session: " . $merchantSession . "<br/>";
            $message .= "Merchant ID: " . $usedAcquirerMerchantId . "<br/>";
            $message .= "Amount: " . $amount . " (cents)<br/>";
            $message .= "Transaction Time: " . $transactionTime . "<br/>";
            $message .= "IP: " . $requestIp . "<br/>";

            $history = new stdClass();
            $email = new stdClass();

            $history->notified = 0;
            $history->amount = ((float) $amount) / 100;
            $history->data = 
                    "\n--\n Paystation Transaction ID: " . $transactionId . "\n" .
                    "\n--\n Status: " . $transactionId . "\n" .
                    "\n";

            $merchant_ref = $merchantReference;
            $xpl = explode(':', $merchant_ref);
            $customer_email = $xpl[0];
            if (!isset($order_id)) {
                $xpl = explode(':', $merchant_ref);

                $customer_email = $xpl[0];
                $order_id = $xpl[1];


                if ($order_id == "test-mode" && $testmode) {
                    $order_id = $xpl[2];
                }
            }

            $dbOrder = $this->getOrder($order_id);
            $completed = ($errorCode == 0);

            if (!$completed) {
                $order_status = $this->payment_params->invalid_status;
                $history->data .= "\n\n" . 'payment with code ' . $errorCode . ' - ' . $errorMessage;

                $order_text = $errorCode . ' - ' . $errorMessage . "\r\n\r\n";
                $email->body = str_replace('<br/>', "\r\n", JText::sprintf('PAYMENT_NOTIFICATION_STATUS', 'Paystation', $errorMessage)) . ' ' . JText::_('STATUS_NOT_CHANGED') . "\r\n\r\n" . $order_text;
                $email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER', 'Paystation', $errorMessage, $dbOrder->order_number);

                $this->modifyOrder($order_id, $order_status, $history, $email);
                exit("Paystation payment failed");
            }

            $order_status = $this->payment_params->verified_status;
            $vars['payment_status'] = 'Accepted';
            $history->notified = 1;
            $email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER', 'Paystation', $vars['payment_status'], $dbOrder->order_number);
            $email->body = str_replace('<br/>', "\r\n", JText::sprintf('PAYMENT_NOTIFICATION_STATUS', 'Paystation', $vars['payment_status'])) . ' ' . JText::sprintf('ORDER_STATUS_CHANGED', $statuses[$order_status]) . "\r\n\r\n";

            $this->modifyOrder($order_id, $order_status, $history, $email);

            exit("Paystation payment successful");
        }
    }

    private function returnURL(&$statuses) {
        $this->app = & JFactory::getApplication();
        $this->url_itemid = empty($Itemid) ? '' : '&Itemid=' . $Itemid;

        $method_id = JRequest::getInt('notif_id', 0);
        $this->pluginParams($method_id);
        $this->payment_params = & $this->plugin_params;
        $testmode = ($this->payment_params->testmode == "TEST");
        $postback = ($this->payment_params->postback == "ENABLED");
        $pstn_pi = trim($this->payment_params->paystation_id);
        $pstn_gi = trim($this->payment_params->gateway_id);
        $merchant_ref = $_GET['merchant_ref'];

        if (empty($this->payment_params))
            return false;

        $QL_amount = '';
        $QL_EC = -1;
        $QL_merchant_session = '';

        if (isset($pstn_pi)) {
            $confirm = $this->transactionVerification($pstn_pi, $_GET['ti'], $QL_amount, $QL_merchant_session, $QL_EC);
        }

        if (!isset($_GET['em'])) {
            $_SESSION['paystation_success'] = false;
            $success = false;
            $display_message = "Sorry, we couldn't find the payment information.\nTransaction ID: " . ((isset($_GET['ti'])) ? $_GET['ti'] : 'Not defined') . "\nError Code: " . ((isset($_GET['ec'])) ? $_GET['ec'] : 'Not defined');
            $display_message .= "Payment information was not included with return URL." .
                    "\n<br />TnxID: " . ((isset($_GET['ti'])) ? $_GET['ti'] : 'Not defined') .
                    "\n<br />ErrCode: " . ((isset($_GET['ec'])) ? $_GET['ec'] : 'Not defined') .
                    "\n<br />TnxSess: " . ((isset($_GET['ms'])) ? $_GET['ms'] : 'Not defined') .
                    "\n<br />Amount: " . ((isset($_GET['am'])) ? $_GET['am'] : 'Not defined');

            exit($display_message);
        }

        $completed = ($_GET['ec'] == "0" && (int) $confirm == 0 && $QL_amount == $_GET['am'] && $_GET['ms'] == $QL_merchant_session);

        $httpsHikashop = HIKASHOP_LIVE;

        $cancel_url = $httpsHikashop .
                'index.php?option=com_hikashop&ctrl=order&task=cancel_order' . $this->url_itemid;

        if (!isset($order_id)) {
            $xpl = explode(':', $merchant_ref);

            $customer_email = $xpl[0];
            $order_id = $xpl[1];

            if ($order_id == "test-mode" && $testmode) {
                $order_id = $xpl[2];
            }
        }

        $dbOrder = $this->getOrder($order_id);
        $cancel_url = $httpsHikashop . 'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id=' . $order_id . $this->url_itemid;
        if (empty($dbOrder)) {
            $this->app->enqueueMessage('Could not load any order for your notification ' . $order_id, 'error');
            $this->app->redirect($cancel_url);

            return false;
        }
        if ($method_id != $dbOrder->order_payment_id)
            return false;
        $this->loadOrderData($dbOrder);
        if ($this->payment_params->debug) {
            echo print_r($vars, true) . "\n\n\n";
            echo print_r($dbOrder, true) . "\n\n\n";
        }

        if ($this->payment_params->debug) {
            echo print_r($dbOrder, true) . "\n\n\n";
        }
        $url = HIKASHOP_LIVE . 'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id=' . $order_id;
        $order_text = "\r\n" . JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE', $dbOrder->order_number, HIKASHOP_LIVE);
        $order_text .= "\r\n" . str_replace('<br/>', "\r\n", JText::sprintf('ACCESS_ORDER_WITH_LINK', $url));

        $return_url = $httpsHikashop . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order_id . $this->url_itemid;

        $history = new stdClass();
        $email = new stdClass();

        $history->notified = 0;
        $history->amount = ((float) $_GET['am']) / 100 . $this->currency->currency_code;
        $history->data = $_GET['ec'] . ': ' . $_GET['em'] .
                "\n--\n Paystation Transaction ID: " . $_GET['ti'] . "\n" .
                "\n" . ob_get_clean();

        if (!$completed) {
            if (!$postback) {
                $order_status = $this->payment_params->invalid_status;
                $history->data .= "\n\n" . 'payment with code ' . $vars['Status'] . ' - ' . $vars['StatusDetail'];

                $order_text = $vars['Status'] . ' - ' . $vars['StatusDetail'] . "\r\n\r\n" . $order_text;
                $email->body = str_replace('<br/>', "\r\n", JText::sprintf('PAYMENT_NOTIFICATION_STATUS', 'Paystation', $vars['Status'])) . ' ' . JText::_('STATUS_NOT_CHANGED') . "\r\n\r\n" . $order_text;
                $email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER', 'Paystation', $vars['Status'], $dbOrder->order_number);

                $this->modifyOrder($order_id, $order_status, $history, $email);
            }

            $this->app->enqueueMessage('Transaction Failed: ' . $_GET['em'], 'error');
            $this->app->redirect($cancel_url);
            return false;
        }
        if (!$postback) {
            $order_status = $this->payment_params->verified_status;
            $vars['payment_status'] = 'Accepted';
            $history->notified = 1;
            $email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER', 'Paystation', $vars['payment_status'], $dbOrder->order_number);
            $email->body = str_replace('<br/>', "\r\n", JText::sprintf('PAYMENT_NOTIFICATION_STATUS', 'Paystation', $vars['payment_status'])) . ' ' . JText::sprintf('ORDER_STATUS_CHANGED', $statuses[$order_status]) . "\r\n\r\n" . $order_text;

            $this->modifyOrder($order_id, $order_status, $history, $email);
        }
        $this->app->enqueueMessage('Paystation transaction successful. ', 'notice');
        $this->app->redirect($return_url);

        return true;
    }

    public function transactionVerification($paystationID, $transactionID, &$QL_amount, &$QL_merchant_session, &$QL_EC) {

        $transactionVerified = '';
        $lookupXML = $this->quickLookup($paystationID, 'ti', $transactionID);
        $p = xml_parser_create();
        xml_parse_into_struct($p, $lookupXML, $vals, $tags);
        xml_parser_free($p);
        for ($i = 0; $i < count($vals); $i++) {

            $key = $vals[$i];
            $key = $key['tag'];
            $val = $i;
            if ($key == "PAYSTATIONERRORCODE") {
                $transactionVerified = (int) $vals[$val]['value'];
                $QL_EC = (int) $transactionVerified;
            } elseif ($key == "PURCHASEAMOUNT") { //19
                $QL_amount = $vals[$val];
                $QL_amount = $QL_amount ['value'];
            } elseif ($key == "MERCHANTSESSION") { //15
                $QL_merchant_session = $vals[$val]['value'];
            } else {
                continue;
            }
        }

        return $transactionVerified;
    }

    public function quickLookup($pi, $type, $value) {
        $url = "https://payments.paystation.co.nz/lookup/";
        $params = "&pi=$pi&$type=$value";

        $method_id = JRequest::getInt('notif_id', 0);
        $this->pluginParams($method_id);
        $this->payment_params = & $this->plugin_params;
        $authenticationKey = trim($this->payment_params->HMACkey);
        $hmacWebserviceName = 'paystation';
        $pstn_HMACTimestamp = time();

        $hmacBody = pack('a*', $pstn_HMACTimestamp) . pack('a*', $hmacWebserviceName) . pack('a*', $params);
        $hmacHash = hash_hmac('sha512', $hmacBody, $authenticationKey);
        $hmacGetParams = '?pstn_HMACTimestamp=' . $pstn_HMACTimestamp . '&pstn_HMAC=' . $hmacHash;

        $url.= $hmacGetParams;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function parseCode($mvalues) {
        $result = '';
        for ($i = 0; $i < count($mvalues); $i++) {
            if (!strcmp($mvalues[$i]["tag"], "QSIRESPONSECODE") && isset($mvalues[$i]["value"])) {
                $result = $mvalues[$i]["value"];
            }
        }
        return $result;
    }

    function getPaymentDefaultValues(&$element) {
        $element->payment_name = 'Paystation';
        $element->payment_description = 'You can pay by credit card using this payment method';
        $element->payment_images = '';

        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->pending_status = 'created';
        $element->payment_params->verified_status = 'confirmed';
    }
}

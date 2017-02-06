<?php
/**
 * Copyright (c) 2017, MasterCard International Incorporated
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of
 * conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its
 * contributors may be used to endorse or promote products derived from this software
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This payment module enables the processing of
 * credit card transactions through the Simplify
 * Commerce framework.
 */
class SimplifyCommerce extends PaymentModule
{
    public $defaultPaymentMode = 'hosted_payments';
    public $defaultModalOverlayColor = '#22A6CA';

    /**
     * Simplify Commerce's module constuctor
     */
    public function __construct()
    {
        $this->name = 'simplifycommerce';
        $this->tab = 'payments_gateways';
        $this->version = '1.2.0';
        $this->author = 'MasterCard';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->module_key = '8b7703c5901ec736bd931bbbb8cfd13c';

        parent::__construct();

        $this->displayName = $this->l('Simplify Commerce');
        $this->description = $this->l('Payments made easy - Start securely accepting credit card payments instantly.');
        $this->confirmUninstall = $this->l('Warning: Are you sure you want to uninstall this module?');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans('No currency has been set for this module.', array(), 'Modules.SimplifyCommerce.Admin');
        }
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }


    public function getBaseLink()
    {
        return __PS_BASE_URI__;
    }

    public function getLangLink()
    {
        return '';
    }

    public function hookDisplayHeader()
    {
        if (!$this->active) {
            return;
        }

        $this->context->controller->addCSS($this->_path.'views/css/style.css', 'all');

        $this->context->controller->addJS($this->_path.'views/js/simplify.js');
        $this->context->controller->addJS($this->_path.'views/js/simplify.form.js');


        $mode = Configuration::get('SIMPLIFY_PAYMENT_MODE');

        if ($mode == "standard") {
            $this->context->controller->registerJavascript(
                'remote-simplifypayments-js',
                'https://www.simplify.com/commerce/v1/simplify.js',
                ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]
            );
        } else {
            $this->context->controller->registerJavascript(
                'remote-simplifypayments-hp',
                'https://www.simplify.com/commerce/simplify.pay.js',
                ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]
            );
        }
    }

    /**
     * Simplify Commerce's module installation
     *
     * @return boolean Install result
     */
    public function install()
    {
        return parent::install()
        && $this->registerHook('paymentOptions')
        && $this->registerHook('orderConfirmation')
        && $this->registerHook('displayHeader')
        && Configuration::updateValue('SIMPLIFY_MODE', 0)
        && Configuration::updateValue('SIMPLIFY_SAVE_CUSTOMER_DETAILS', 1)
        && Configuration::updateValue('SIMPLIFY_PAYMENT_MODE', $this->defaultPaymentMode)
        && Configuration::updateValue('SIMPLIFY_OVERLAY_COLOR', $this->defaultModalOverlayColor)
        && Configuration::updateValue('SIMPLIFY_PAYMENT_ORDER_STATUS', (int)Configuration::get('PS_OS_PAYMENT'))
        && $this->createDatabaseTables();
    }

    /**
     * Simplify Customer tables creation
     *
     * @return boolean Database tables installation result
     */
    public function createDatabaseTables()
    {
        return Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'simplify_customer` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `customer_id` varchar(32) NOT NULL, `simplify_customer_id` varchar(32) NOT NULL, `date_created` datetime NOT NULL, PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`), KEY `simplify_customer_id` (`simplify_customer_id`)) ENGINE='.
            _MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');
    }

    /**
     * Simplify Commerce's module uninstallation. Remove the config values and delete the tables.
     *
     * @return boolean Uninstall result
     */
    public function uninstall()
    {
        return parent::uninstall()
        && Configuration::deleteByName('SIMPLIFY_MODE')
        && Configuration::deleteByName('SIMPLIFY_SAVE_CUSTOMER_DETAILS')
        && Configuration::deleteByName('SIMPLIFY_PUBLIC_KEY_TEST')
        && Configuration::deleteByName('SIMPLIFY_PUBLIC_KEY_LIVE')
        && Configuration::deleteByName('SIMPLIFY_PRIVATE_KEY_TEST')
        && Configuration::deleteByName('SIMPLIFY_PRIVATE_KEY_LIVE')
        && Configuration::deleteByName('SIMPLIFY_PAYMENT_ORDER_STATUS')
        && Configuration::deleteByName('SIMPLIFY_PAYMENT_MODE')
        && Configuration::deleteByName('SIMPLIFY_OVERLAY_COLOR')
        && Db::getInstance()->Execute('DROP TABLE IF EXISTS`'._DB_PREFIX_.'simplify_customer`');
    }

    /**
     * Display the Simplify Commerce's payment form
     *
     * @return string Simplify Commerce's payment form
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return false;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        include(dirname(__FILE__).'/lib/Simplify.php');

        $api_keys = $this->getSimplifyAPIKeys();
        Simplify::$public_key = $api_keys->public_key;
        Simplify::$private_key = $api_keys->private_key;

        // If flag checked in the settings, look up customer details in the DB
        if (Configuration::get('SIMPLIFY_SAVE_CUSTOMER_DETAILS')) {
            $this->smarty->assign('show_save_customer_details_checkbox', true);
            $simplify_customer_id = Db::getInstance()->getValue('SELECT simplify_customer_id FROM '.
                _DB_PREFIX_.'simplify_customer WHERE customer_id = '.(int)$this->context->cookie->id_customer);

            if ($simplify_customer_id) {
                // look up the customer's details
                try {
                    $customer = SimplifyCustomer::findCustomer($simplify_customer_id);
                    $this->smarty->assign('show_saved_card_details', true);
                    $this->smarty->assign('customer_details', $customer);
                } catch (SimplifyApiException $e) {
                    if (class_exists('Logger')) {
                        Logger::addLog($this->l('Simplify Commerce - Error retrieving customer'), 1, null, 'Cart', (int)$this->context->cart->id, true);
                    }

                    if ($e->getErrorCode() == 'object.not.found') {
                        $this->deleteCustomerFromDB();
                    } // remove the old customer from the database, as it no longer exists in Simplify
                }
            }
        }

        // Create empty object by default
        $cardholder_details = new stdClass;

        // Send the cardholder's details with the payment
        if (isset($this->context->cart->id_address_invoice)) {
            $invoice_address = new Address((int)$this->context->cart->id_address_invoice);

            if ($invoice_address->id_state) {
                $state = new State((int)$invoice_address->id_state);

                if (Validate::isLoadedObject($state)) {
                    $invoice_address->state = $state->iso_code;
                }
            }

            $cardholder_details = $invoice_address;
        }

        $currency = new Currency((int)($this->context->cart->id_currency));

        // Set js variables to send in card tokenization
        $this->smarty->assign('simplify_public_key', Simplify::$public_key);

        $this->smarty->assign('firstname', $cardholder_details->firstname);
        $this->smarty->assign('lastname', $cardholder_details->lastname);
        $this->smarty->assign('city', $cardholder_details->city);
        $this->smarty->assign('address1', $cardholder_details->address1);
        $this->smarty->assign('address2', $cardholder_details->address2);
        $this->smarty->assign('state', isset($cardholder_details->state)?$cardholder_details->state:'');
        $this->smarty->assign('postcode', $cardholder_details->postcode);

        //fields related to hosted payments
        $this->smarty->assign('hosted_payment_name', $this->context->shop->name);
        $this->smarty->assign('hosted_payment_description', $this->context->shop->name.$this->l(' Order Number: ').(int)$this->context->cart->id);
        $this->smarty->assign('hosted_payment_reference', 'Order Number'.(int)$this->context->cart->id);
        $this->smarty->assign('hosted_payment_amount', ($this->context->cart->getOrderTotal() * 100));

        $this->smarty->assign('payment_mode', Configuration::get('SIMPLIFY_PAYMENT_MODE'));
        $this->smarty->assign('overlay_color', Configuration::get('SIMPLIFY_OVERLAY_COLOR') != null ? Configuration::get('SIMPLIFY_OVERLAY_COLOR') : $this->defaultModalOverlayColor);

        $this->smarty->assign('module_dir', $this->_path);

        $this->smarty->assign('currency_iso', $currency->iso_code);

        $option = $this->getPaymentOption();

        return [$option];
    }

    public function getPaymentOption()
    {
        $option = new PaymentOption();
        $option->setCallToActionText($this->trans('Pay by Credit Card', array(), 'Modules.SimplifyCommerce.Admin'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setForm($this->fetch('module:simplifycommerce/views/templates/front/payment.tpl'));

        return $option;
    }

    /**
     * Display a confirmation message after an order has been placed.
     *
     * @param array $params Hook parameters
     * @return string Simplify Commerce's payment confirmation screen
     */
    public function hookOrderConfirmation($params)
    {
        if (!isset($params['objOrder']) || ($params['objOrder']->module != $this->name)) {
            return false;
        }

        if ($params['objOrder'] && Validate::isLoadedObject($params['objOrder']) && isset($params['objOrder']->valid)) {
            $order = array('reference' =>
                isset($params['objOrder']->reference) ? $params['objOrder']->reference : '#'.
                    sprintf('%06d', $params['objOrder']->id), 'valid' => $params['objOrder']->valid);
            $this->smarty->assign('simplify_order', $order);
        }

        return $this->display(__FILE__, 'views/templates/hook/order-confirmation.tpl');
    }

    /**
     * Process a payment with Simplify Commerce.
     * Depeding on the customer's input, we can delete/update
     * existing customer card details and charge a payment
     * from the generated card token.
     */
    public function processPayment()
    {
        if (!$this->active) {
            return false;
        }

        $currency_order = new Currency((int)($this->context->cart->id_currency));

        // Extract POST paramaters from the request
        $simplify_token_post = Tools::getValue('simplifyToken');
        $delete_customer_card_post = Tools::getValue('deleteCustomerCard');
        $save_customer_post = Tools::getValue('saveCustomer');

        Logger::addLog($this->l('Simplify Commerce - Save Customer = '.$save_customer_post), 1, null, 'Cart', (int)$this->context->cart->id, true);

        $charge_customer_card = Tools::getValue('chargeCustomerCard');

        $token = !empty($simplify_token_post) ? $simplify_token_post : null;
        $should_delete_customer = !empty($delete_customer_card_post) ? $delete_customer_card_post : false;
        $should_save_customer = !empty($save_customer_post) ? $save_customer_post : false;
        $should_charge_customer_card = !empty($charge_customer_card) ? $charge_customer_card : false;

        include(dirname(__FILE__).'/lib/Simplify.php');
        $api_keys = $this->getSimplifyAPIKeys();
        Simplify::$public_key = $api_keys->public_key;
        Simplify::$private_key = $api_keys->private_key;

        // look up the customer
        $simplify_customer = Db::getInstance()->getRow('
            SELECT simplify_customer_id FROM '._DB_PREFIX_.'simplify_customer
            WHERE customer_id = '.(int)$this->context->cookie->id_customer);

        $simplify_customer_id = $this->getSimplifyCustomerID($simplify_customer['simplify_customer_id']);


        // The user has chosen to delete the credit card, so we need to delete the customer
        if (isset($simplify_customer_id) && $should_delete_customer) {
            try {
                // delete on simplify.com
                $customer = SimplifyCustomer::findCustomer($simplify_customer_id);
                $customer->deleteCustomer();
            } catch (SimplifyApiException $e) {
                // can't find the customer on Simplify, so no need to delete
                if (class_exists('Logger')) {
                    Logger::addLog($this->l('Simplify Commerce - Error retrieving customer'), 1, null, 'Cart', (int)$this->context->cart->id, true);
                }
            }

            $this->deleteCustomerFromDB();
            $simplify_customer_id = null;
        }


        // The user has chosen to save the credit card details
        if ($should_save_customer == 'on') {
            Logger::addLog($this->l('Simplify Commerce - $should_save_customer = '.$should_save_customer), 1, null, 'Cart', (int)$this->context->cart->id, true);
            // Customer exists already so update the card details from the card token
            if (isset($simplify_customer_id)) {
                try {
                    $customer = SimplifyCustomer::findCustomer($simplify_customer_id);
                    $updates = array(
                        'email' => (string)$this->context->cookie->email,
                        'name' => (string)$this->context->cookie->customer_firstname.' '.$this->context->cookie->customer_lastname,
                        'token' => $token
                    );

                    $customer->setAll($updates);
                    $customer->updateCustomer();
                } catch (SimplifyApiException $e) {
                    if (class_exists('Logger')) {
                        Logger::addLog($this->l('Simplify Commerce - Error updating customer card details'), 1, null, 'Cart', (int)$this->context->cart->id, true);
                    }
                }
            } else {
                $simplify_customer_id = $this->createNewSimplifyCustomer($token); // Create a new customer from the card token
            }
        }

        $charge = (float)$this->context->cart->getOrderTotal();



        try {
            $amount = $charge * 100; // Cart total amount
            $description = $this->context->shop->name.$this->l(' Order Number: ').(int)$this->context->cart->id;

            if (isset($simplify_customer_id) && ($should_charge_customer_card == 'true' || $should_save_customer == 'on')) {
                $simplify_payment = SimplifyPayment::createPayment(array(
                    'amount' => $amount,
                    'customer' => $simplify_customer_id, // Customer stored in the database
                    'description' => $description,
                    'currency' =>  $currency_order->iso_code
                ));
            } else {
                $simplify_payment = SimplifyPayment::createPayment(array(
                    'amount' => $amount,
                    'token' => $token, // Token returned by Simplify Card Tokenization
                    'description' => $description,
                    'currency' =>  $currency_order->iso_code
                ));
            }

            $payment_status = $simplify_payment->paymentStatus;
        } catch (SimplifyApiException $e) {
            $this->failPayment($e->getMessage());
        }

        if ($payment_status != 'APPROVED') {
            $this->failPayment('The transaction was '.$payment_status);
        }

        // Log the transaction
        $message = $this->l('Simplify Commerce Transaction Details:').'\n\n'.
            $this->l('Payment ID:').' '.$simplify_payment->id.'\n'.
            $this->l('Payment Status:').' '.$simplify_payment->paymentStatus.'\n'.
            $this->l('Amount:').' '.$simplify_payment->amount * 0.01.'\n'.
            $this->l('Currency:').' '.$simplify_payment->currency.'\n'.
            $this->l('Description:').' '.$simplify_payment->description.'\n'.
            $this->l('Auth Code:').' '.$simplify_payment->authCode.'\n'.
            $this->l('Fee:').' '.$simplify_payment->fee * 0.01.'\n'.
            $this->l('Card Last 4:').' '.$simplify_payment->card->last4.'\n'.
            $this->l('Card Expiry Year:').' '.$simplify_payment->card->expYear.'\n'.
            $this->l('Card Expiry Month:').' '.$simplify_payment->card->expMonth.'\n'.
            $this->l('Card Type:').' '.$simplify_payment->card->type.'\n';





        // Create the PrestaShop order in database


        $this->validateOrder(
            (int)$this->context->cart->id,
            (int)Configuration::get('SIMPLIFY_PAYMENT_ORDER_STATUS'),
            $charge,
            $this->displayName,
            $message,
            array(),
            null,
            false,
            $this->context->customer->secure_key
        );



        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $new_order = new Order((int)$this->currentOrder);


            if (Validate::isLoadedObject($new_order)) {
                $payment = $new_order->getOrderPaymentCollection();

                if (isset($payment[0])) {
                    $payment[0]->transaction_id = pSQL($simplify_payment->id);
                    $payment[0]->save();
                }
            }
        }


        if (Configuration::get('SIMPLIFY_MODE')) {
            Configuration::updateValue('SIMPLIFYCOMMERCE_CONFIGURED', true);
        }


        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            Tools::redirect(Link::getPageLink('order-confirmation.php', null, null).
                '?id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->id.'&id_order='.
                (int)$this->currentOrder.'&key='.$this->context->customer->secure_key, '');
        } else {
            Tools::redirect($this->context->link->getPagelink('order-confirmation.php', null, null,
                array('id_cart' => (int)$this->context->cart->id, 'id_module' => (int)$this->id,
                    'id_order' => (int)$this->currentOrder, 'key' => $this->context->customer->secure_key)));
        }
        exit;
    }

    /**
     * Function to check if customer still exists in Simplify and if not to delete them from the DB.
     *
     * @return string Simplify customer's id.
     */
    private function getSimplifyCustomerID($customer_id)
    {
        $simplify_customer_id = null;

        try {
            $customer = SimplifyCustomer::findCustomer($customer_id);
            $simplify_customer_id = $customer->id;
        } catch (SimplifyApiException $e) {
            // can't find the customer on Simplify, so no need to delete
            if (class_exists('Logger')) {
                Logger::addLog($this->l('Simplify Commerce - Error retrieving customer'), 1, null, 'Cart', (int)$this->context->cart->id, true);
            }

            if ($e->getErrorCode() == 'object.not.found') {
                $this->deleteCustomerFromDB();
            } // remove the old customer from the database, as it no longer exists in Simplify
        }

        return $simplify_customer_id;
    }

    /**
     * Function to create a new Simplify customer and to store its id in the database.
     *
     * @return string Simplify customer's id.
     */
    private function deleteCustomerFromDB()
    {
        Db::getInstance()->Execute('DELETE FROM '._DB_PREFIX_.'simplify_customer WHERE customer_id = '.(int)$this->context->cookie->id_customer.';');
    }

    /**
     * Function to create a new Simplify customer and to store its id in the database.
     *
     * @return string Simplify customer's id.
     */
    private function createNewSimplifyCustomer($token)
    {
        try {
            $customer = SimplifyCustomer::createCustomer(array(
                'email' => (string)$this->context->cookie->email,
                'name' => (string)$this->context->cookie->customer_firstname.' '.(string)$this->context->cookie->customer_lastname,
                'token' => $token,
                'reference' => $this->context->shop->name.$this->l(' Customer ID:').' '.(int)$this->context->cookie->id_customer
            ));

            $simplify_customer_id = pSQL($customer->id);

            Db::getInstance()->Execute('
                INSERT INTO '._DB_PREFIX_.'simplify_customer (id, customer_id, simplify_customer_id, date_created)
                VALUES (NULL, '.(int)$this->context->cookie->id_customer.', \''.$simplify_customer_id.'\', NOW())');
        } catch (SimplifyApiException $e) {
            $this->failPayment($e->getMessage());
        }

        return $simplify_customer_id;
    }

    /**
     * Function to return the user's Simplify API Keys depending on the account mode in the settings.
     *
     * @return object Simple object containin the Simplify public & private key values.
     */
    private function getSimplifyAPIKeys()
    {
        $api_keys = new stdClass;
        $api_keys->public_key = Configuration::get('SIMPLIFY_MODE') ?
            Configuration::get('SIMPLIFY_PUBLIC_KEY_LIVE') : Configuration::get('SIMPLIFY_PUBLIC_KEY_TEST');
        $api_keys->private_key = Configuration::get('SIMPLIFY_MODE') ?
            Configuration::get('SIMPLIFY_PRIVATE_KEY_LIVE') : Configuration::get('SIMPLIFY_PRIVATE_KEY_TEST');

        return $api_keys;
    }

    /**
     * Function to log a failure message and redirect the user
     * back to the payment processing screen with the error.
     *
     * @param string $message Error message to log and to display to the user
     */
    private function failPayment($message)
    {
        if (class_exists('Logger')) {
            Logger::addLog($this->l('Simplify Commerce - Payment transaction failed').' '.$message, 1, null, 'Cart', (int)$this->context->cart->id, true);
        }

        $controller = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc.php' : 'order.php';
        error_log($message);
        $location = $this->context->link->getPageLink($controller).(strpos($controller, '?') !== false ? '&' : '?').
            'step=3&simplify_error=There was a problem with your payment: '.$message.'#simplify_error';
        Tools::redirect($location);
        exit;
    }

    /**
     * Check settings requirements to make sure the Simplify Commerce's
     * API keys are set.
     *
     * @return boolean Whether the API Keys are set or not.
     */
    public function checkSettings()
    {
        if (Configuration::get('SIMPLIFY_MODE')) {
            return Configuration::get('SIMPLIFY_PUBLIC_KEY_LIVE') != '' && Configuration::get('SIMPLIFY_PRIVATE_KEY_LIVE') != '';
        } else {
            return Configuration::get('SIMPLIFY_PUBLIC_KEY_TEST') != '' && Configuration::get('SIMPLIFY_PRIVATE_KEY_TEST') != '';
        }
    }

    /**
     * Check key prefix
     * API keys are set.
     *
     * @return boolean Whether the API Keys are set or not.
     */
    public function checkKeyPrefix()
    {
        if (Configuration::get('SIMPLIFY_MODE')) {
            return strpos(Configuration::get('SIMPLIFY_PUBLIC_KEY_LIVE'), 'lvpb_') === 0;
        } else {
            return strpos(Configuration::get('SIMPLIFY_PUBLIC_KEY_TEST'), 'sbpb_') === 0;
        }
    }

    /**
     * Check technical requirements to make sure the Simplify Commerce's module will work properly
     *
     * @return array Requirements tests results
     */
    public function checkRequirements()
    {
        $tests = array('result' => true);
        $tests['curl'] = array(
            'name' => $this->l('PHP cURL extension must be enabled on your server'),
            'result' => extension_loaded('curl')
        );

        if (Configuration::get('SIMPLIFY_MODE')) {
            $tests['ssl'] = array(
                'name' => $this->l('SSL must be enabled on your store (before entering Live mode)'),
                'result' => Configuration::get('PS_SSL_ENABLED') || (!empty($_SERVER['HTTPS']) && Tools::strtolower($_SERVER['HTTPS']) != 'off')
            );
        }

        $tests['php52'] = array(
            'name' => $this->l('Your server must run PHP 5.3 or greater'),
            'result' => version_compare(PHP_VERSION, '5.3.0', '>=')
        );

        $tests['configuration'] = array(
            'name' => $this->l('You must set your Simplify Commerce API Keys'),
            'result' => $this->checkSettings()
        );

        if ($tests['configuration']['result']) {
            $tests['keyprefix'] = array(
                'name' => $this->l('Your API Keys appears to be invalid. Please make sure that you specified the right keys.'),
                'result' => $this->checkKeyPrefix()
            );
        }

        foreach ($tests as $k => $test) {
            if ($k != 'result' && !$test['result']) {
                $tests['result'] = false;
            }
        }

        return $tests;
    }

    /**
     * Display the Simplify Commerce's module settings page
     * for the user to set their API Key pairs and choose
     * whether their customer's can save their card details for
     * repeate visits.
     *
     * @return string Simplify settings page
     */
    public function getContent()
    {
        $html = '';
        // Update Simplify settings
        if (Tools::isSubmit('SubmitSimplify')) {
            $configuration_values = array(
                'SIMPLIFY_MODE' => Tools::getValue('simplify_mode'),
                'SIMPLIFY_SAVE_CUSTOMER_DETAILS' => Tools::getValue('simplify_save_customer_details'),
                'SIMPLIFY_PUBLIC_KEY_TEST' => Tools::getValue('simplify_public_key_test'),
                'SIMPLIFY_PUBLIC_KEY_LIVE' => Tools::getValue('simplify_public_key_live'),
                'SIMPLIFY_PRIVATE_KEY_TEST' => Tools::getValue('simplify_private_key_test'),
                'SIMPLIFY_PRIVATE_KEY_LIVE' => Tools::getValue('simplify_private_key_live'),
                'SIMPLIFY_PAYMENT_ORDER_STATUS' => (int)Tools::getValue('simplify_payment_status'),
                'SIMPLIFY_OVERLAY_COLOR' => Tools::getValue('simplify_overlay_color'),
                'SIMPLIFY_PAYMENT_MODE' => Tools::getValue('simplify_payment_mode')
            );



            $ok = true;

            foreach ($configuration_values as $configuration_key => $configuration_value) {
                $ok &= Configuration::updateValue($configuration_key, $configuration_value);
            }
            if ($ok) {
                $html .= $this->displayConfirmation($this->l('Settings updated successfully'));
            } else {
                $html .= $this->displayError($this->l('Error occurred during settings update'));
            }
        }

        $requirements = $this->checkRequirements();

        $this->smarty->assign('path', $this->_path);
        $this->smarty->assign('module_name', $this->name);
        $this->smarty->assign('http_host', urlencode($_SERVER['HTTP_HOST']));
        $this->smarty->assign('requirements', $requirements);
        $this->smarty->assign('result', $requirements['result']);
        $this->smarty->assign('simplify_mode', Configuration::get('SIMPLIFY_MODE'));
        $this->smarty->assign('private_key_test', Configuration::get('SIMPLIFY_PRIVATE_KEY_TEST'));
        $this->smarty->assign('public_key_test', Configuration::get('SIMPLIFY_PUBLIC_KEY_TEST'));
        $this->smarty->assign('private_key_live', Configuration::get('SIMPLIFY_PRIVATE_KEY_LIVE'));
        $this->smarty->assign('public_key_live', Configuration::get('SIMPLIFY_PUBLIC_KEY_LIVE'));
        $this->smarty->assign('save_customer_details', Configuration::get('SIMPLIFY_SAVE_CUSTOMER_DETAILS'));
        $this->smarty->assign('statuses', OrderState::getOrderStates((int)$this->context->cookie->id_lang));
        $this->smarty->assign('request_uri', Tools::safeOutput($_SERVER['REQUEST_URI']));
        $this->smarty->assign('payment_mode', Configuration::get('SIMPLIFY_PAYMENT_MODE'));
        $this->smarty->assign('overlay_color', Configuration::get('SIMPLIFY_OVERLAY_COLOR') != null ? Configuration::get('SIMPLIFY_OVERLAY_COLOR') : $this->defaultModalOverlayColor);
        $this->smarty->assign('statuses_options', array(array('name' => 'simplify_payment_status', 'label' =>
            $this->l('Successful Payment Order Status'), 'current_value' => Configuration::get('SIMPLIFY_PAYMENT_ORDER_STATUS'))));

        $base_img = $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/img/';

        $this->smarty->assign('ok_icon_link', $base_img . 'checkmark-24.ico');
        $this->smarty->assign('nok_icon_link', $base_img . 'x-mark-24.ico');

        $html .= $this->display(__FILE__, 'views/templates/hook/module-wrapper.tpl');
        return $html;
    }
}

<?php
/**
 * Copyright (c) 2017-2023 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */


use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButton;
use PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButtonsCollection;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This payment module enables the processing of
 * card transactions through the Simplify
 * Commerce framework.
 */
class SimplifyCommerce extends PaymentModule
{
    const TXN_MODE_PURCHASE = 'purchase';
    const TXN_MODE_AUTHORIZE = 'authorize';

    const PAYMENT_OPTION_MODAL = 'modal';
    const PAYMENT_OPTION_EMBEDDED = 'embedded';

    /**
     * @var string
     */
    public $defaultModalOverlayColor = '#22A6CA';

    /**
     * @var string
     */
    protected $defaultTitle;

    /**
     * @var string
     */
    protected $controllerAdmin;


    /**
     * Simplify Commerce's module constructor
     */
    public function __construct()
    {
        $this->name = 'simplifycommerce';
        $this->tab = 'payments_gateways';
        $this->version = '2.4.0';
        $this->author = 'Mastercard';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->module_key = '8b7703c5901ec736bd931bbbb8cfd13c';

        parent::__construct();

        $this->displayName = $this->l('Mastercard Payment Gateway Services - Simplify');
        $this->description = $this->l('Payments made easy - Start securely accepting card payments instantly.');
        $this->confirmUninstall = $this->l('Warning: Are you sure you want to uninstall this module?');
        $this->defaultTitle = $this->l('Pay with Card');
        $this->controllerAdmin = 'AdminSimplify';

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans(
                'No currency has been set for this module.',
                array(),
                'Modules.SimplifyCommerce.Admin'
            );
        }
    }

    /**
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->class_name = $this->controllerAdmin;
        $tab->active = 1;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->name;
        }
        $tab->id_parent = -1;
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName($this->controllerAdmin);
        $tab = new Tab($id_tab);
        if (Validate::isLoadedObject($tab)) {
            return $tab->delete();
        }

        return true;
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
        

        if (Configuration::get('SIMPLIFY_ENABLED_PAYMENT_WINDOW')) {
            if (Configuration::get('SIMPLIFY_PAYMENT_OPTION') === self::PAYMENT_OPTION_EMBEDDED) {
                $this->context->controller->addJS($this->_path.'views/js/simplify.embedded.js');
            } else {
                $this->context->controller->addJS($this->_path.'views/js/simplify.js');
                $this->context->controller->addJS($this->_path.'views/js/simplify.form.js');
            }
        }

        $this->context->controller->registerJavascript(
            'remote-simplifypayments-hp',
            'https://www.simplify.com/commerce/simplify.pay.js',
            ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]
        );
    }

    /**
     * Simplify Commerce module adding ajax
     *
     * @return bool Install result
     */
    public function hookBackOfficeHeader()
    {
        if (!$this->active) {
            return;
        }

        // Add JavaScript file for handling partial refunds
        $this->context->controller->addJS($this->_path . 'views/js/refund.js', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/refund.css', 'all');

        // Get the admin AJAX link for handling requests
        $adminAjaxLink = $this->context->link->getAdminLink('AdminSimplify');
        // Define JavaScript values to use in AJAX URL
        Media::addJsDef(array(
            "adminajax_link" => $adminAjaxLink
        ));

        $orderId = Tools::getValue('id_order');

        if (Validate::isUnsignedId($orderId)) {
            $refundData = array(); // Initialize an array to store refund data

            // Query the database to fetch refund data for the given order ID
            $results = Db::getInstance()->executeS('SELECT refund_description, refund_id, amount,comment, date_created FROM ' . _DB_PREFIX_ . 'refund_table WHERE order_id = ' . (int)$orderId);

            if (!empty($results)) {
                // Loop through the results and store refund data in the array
                foreach ($results as $result) {
                    $refundData[] = array(
                        'refund_description' => $result['refund_description'],
                        'refund_id' => $result['refund_id'],
                        'amount' => $result['amount'],
                        'comment' => $result['comment'],
                        'date_created' => $result['date_created']
                    );
                }

                // Convert the refund data array to a JavaScript array
                $refundDataJS = json_encode($refundData);
                // Add the JavaScript array to the page
                Media::addJsDef(array(
                    'refundData' => $refundDataJS
                ));
            }
        }

         if (Validate::isUnsignedId($orderId)) {
            $captureData = array(); // Initialize an array to store refund data

            // Query the database to fetch refund data for the given order ID
            $results = Db::getInstance()->executeS('SELECT payment_transcation_id, amount, comment, transcation_date FROM ' . _DB_PREFIX_ . 'capture_table WHERE order_id = ' . (int)$orderId);

            if (!empty($results)) {
                // Loop through the results and store refund data in the array
                foreach ($results as $result) {
                    $captureData[] = array(
                        'payment_transcation_id' => $result['payment_transcation_id'],
                        'amount' => $result['amount'],
                        'comment' => $result['comment'],
                        'transcation_date' => $result['transcation_date']
                    );
                }

                // Convert the refund data array to a JavaScript array
                $captureDataJS = json_encode($captureData);
                // Add the JavaScript array to the page
                Media::addJsDef(array(
                    'captureData' => $captureDataJS
                ));
            }
        }

        if (Validate::isUnsignedId($orderId)) {
            $total_amount = 0;
            // Define the variables used in the query
            $tableName = 'refund_table'; // Replace with your actual table name
            $order_id = 'order_id'; // Replace with the appropriate column name for order ID

            // Build the SQL query using pSQL
            $sql = 'SELECT ' . pSQL($order_id) . ', SUM(amount) AS total_amount FROM ' . pSQL(_DB_PREFIX_ . $tableName) . ' WHERE ' . pSQL($order_id) . ' = \'' . pSQL($orderId) . '\' AND comment = "Transaction Successful" GROUP BY ' . pSQL($order_id);

            // Get the total_amount using Db::getInstance()->getValue()
            $result = Db::getInstance()->executeS($sql);
            if (!empty($result)) {
                $total_amount = $result[0]['total_amount'];

                // Add the JavaScript array to the page
                Media::addJsDef(array(
                    'refundmaxamount' => $total_amount
                ));
            }
        }
    }


    /**
     * Simplify Commerce's module installation
     *
     * @return boolean Install result
     */
    public function install()
    {
        // Install admin tab
        if (!$this->installTab()) {
            return false;
        }

        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('orderConfirmation')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayAdminOrderLeft')
            && $this->registerHook('actionGetAdminOrderButtons')
            && Configuration::updateValue('SIMPLIFY_MODE', 0)
            && Configuration::updateValue('SIMPLIFY_SAVE_CUSTOMER_DETAILS', 1)
            && Configuration::updateValue('SIMPLIFY_OVERLAY_COLOR', $this->defaultModalOverlayColor)
            && Configuration::updateValue('SIMPLIFY_PAYMENT_ORDER_STATUS', (int)Configuration::get('PS_OS_PAYMENT'))
            && Configuration::updateValue('SIMPLIFY_PAYMENT_TITLE', $this->defaultTitle)
            && Configuration::updateValue('SIMPLIFY_TXN_MODE', self::TXN_MODE_PURCHASE)
            && $this->createCustomerTable()
            && $this->createCaptureTable()
            && $this->createRefundTable()
            && $this->installOrderState();
    }

    /**
     * Add buttons to main buttons bar
     *
     * @return void
     */
    public function hookActionGetAdminOrderButtons(array $params)
    {
        if ($this->active == false) {
            return;
        }

        $order = new Order($params['id_order']);
        if ($order->payment != $this->displayName) {
            return;
        }

        $isAuthorized = $order->current_state == Configuration::get('SIMPLIFY_OS_AUTHORIZED');
        $canVoid = $isAuthorized;
        $canCapture = $isAuthorized;
        $canRefund = $order->current_state == Configuration::get('PS_OS_PAYMENT');
        $canPatialRefund = $order->current_state == Configuration::get('PS_OS_PAYMENT') || $order->current_state == Configuration::get('SIMPLIFY_OS_PARTIAL_REFUND');;
        $canAction = $isAuthorized || $canVoid || $canCapture || $canPatialRefund ;

        if (!$canAction) {
            return;
        }

        $link = new Link();

        /** @var ActionsBarButtonsCollection $bar */
        $bar = $params['actions_bar_buttons_collection'];

        if ($canCapture) {
            $captureUrl = $link->getAdminLink(
                'AdminSimplify',
                true,
                [],
                [
                    'action'   => 'capture',
                    'id_order' => $order->id,
                ]
            );
            $bar->add(
                new ActionsBarButton(
                    'btn-action',
                    ['href' => $captureUrl],
                    $this->l('Capture Payment')
                )
            );
        }

        if ($canRefund) {
            $refundUrl = $link->getAdminLink(
                'AdminSimplify',
                true,
                [],
                [
                    'action'   => 'refund',
                    'id_order' => $order->id,
                ]
            );
            $bar->add(
                new ActionsBarButton(
                    'btn-action',
                    ['id' => 'fullrefund'],
                    $this->l('Full Refund')
                )
            );
        }

        if ($canPatialRefund) {
            $partialrefundUrl = $link->getAdminLink(
                'AdminSimplify',
                true,
                [],
                [
                    'action'   => 'partialrefund',
                    'id_order' => $order->id,
                ]
            );
            $bar->add(
                new ActionsBarButton(
                    'btn-action',
                    [
                    'class' => 'partialrefund',
                    'id'    => 'refundpartial',
                    ],
                    $this->l('Partial Refund')
                )
            );

        }

        if ($canVoid) {
            $voidUrl = $link->getAdminLink(
                'AdminSimplify',
                true,
                [],
                [
                    'action'   => 'void',
                    'id_order' => $order->id,
                ]
            );
            $bar->add(
                new ActionsBarButton(
                    'btn-action',
                    ['href' => $voidUrl],
                    $this->l('Reverse Authorization')
                )
            );
        }
    }

    /**
     * @param $params
     *
     * @return false|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayAdminOrderLeft($params)
    {
        if ($this->active == false) {
            return '';
        }

        $order = new Order($params['id_order']);
        if ($order->payment != $this->displayName) {
            return '';
        }

        $isAuthorized = $order->current_state == Configuration::get('SIMPLIFY_OS_AUTHORIZED');
        $canVoid = $isAuthorized;
        $canCapture = $isAuthorized;
        $canRefund = $order->current_state == Configuration::get('PS_OS_PAYMENT');

        $canAction = $isAuthorized || $canVoid || $canCapture || $canRefund;

        $this->smarty->assign(
            array(
                'module_dir'         => $this->_path,
                'order'              => $order,
                'simplify_order_ref' => (string)$order->id_cart,
                'can_void'           => $canVoid,
                'can_capture'        => $canCapture,
                'can_refund'         => $canRefund,
                'is_authorized'      => $isAuthorized,
                'can_action'         => $canAction,
            )
        );

        return $this->display(__FILE__, 'views/templates/hook/order_actions.tpl');
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installOrderState()
    {
        if (!Configuration::get('SIMPLIFY_OS_AUTHORIZED')
            || !Validate::isLoadedObject(new OrderState(Configuration::get('SIMPLIFY_OS_AUTHORIZED')))) {
            $order_state = new OrderState();
            foreach (Language::getLanguages() as $language) {
                $order_state->name[$language['id_lang']] = 'Payment Authorized';
                $order_state->template[$language['id_lang']] = 'payment';
            }
            $order_state->send_email = true;
            $order_state->color = '#4169E1';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = true;
            $order_state->paid = true;
            $order_state->invoice = false;
            if ($order_state->add()) {
                $source = _PS_ROOT_DIR_.'/img/os/10.gif';
                $destination = _PS_ROOT_DIR_.'/img/os/'.(int)$order_state->id.'.gif';
                copy($source, $destination);
            }

            return Configuration::updateValue('SIMPLIFY_OS_AUTHORIZED', (int)$order_state->id);
        }
        if (!Configuration::get('SIMPLIFY_OS_PARTIAL_REFUND')
            || !Validate::isLoadedObject(new OrderState(Configuration::get('SIMPLIFY_OS_PARTIAL_REFUND')))) {
            $order_state = new OrderState();
            foreach (Language::getLanguages() as $language) {
                $order_state->name[$language['id_lang']] = 'Partial Refund';
                $order_state->template[$language['id_lang']] = 'refund';
            }
            $order_state->send_email = true;
            $order_state->color = '#01B887';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = true;
            $order_state->paid = true;
            $order_state->invoice = false;
            if ($order_state->add()) {
                $source = _PS_ROOT_DIR_.'/img/os/15.gif';
                $destination = _PS_ROOT_DIR_.'/img/os/'.(int)$order_state->id.'.gif';
                copy($source, $destination);
            }

            return Configuration::updateValue('SIMPLIFY_OS_PARTIAL_REFUND', (int)$order_state->id);
        }

        return true;
    }

    /**
     * Simplify Customer tables creation
     *
     * @return boolean Database tables installation result
     */
    public function createCustomerTable()
    {
        return Db::getInstance()->Execute(
            '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'simplify_customer` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `customer_id` varchar(32) NOT NULL, `simplify_customer_id` varchar(32) NOT NULL, `date_created` datetime NOT NULL, PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`), KEY `simplify_customer_id` (`simplify_customer_id`)) ENGINE='.
            _MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'
        );
    }

    /**
     * Simplify capture details creation
     *
     * @return boolean Database tables installation result
     */
    public function createCaptureTable()
    {
        return Db::getInstance()->Execute(
            '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'capture_table` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`order_id` int(10) unsigned NOT NULL,
              `capture_transcation_id` varchar(32) NOT NULL, `payment_transcation_id` varchar(32) NOT NULL, `amount` decimal(10,2) NOT NULL, `comment` varchar(100) NOT NULL, `transcation_date` datetime NOT NULL,  PRIMARY KEY (`id`)) ENGINE='.
            _MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'
        );
    }

    /**
     * Simplify refund details creation
     *
     * @return boolean Database tables installation result
     */
    public function createRefundTable()
    {
        return Db::getInstance()->Execute(
            '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'refund_table` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`order_id` int(10) unsigned NOT NULL,
              `refund_id` varchar(32) NOT NULL, `transcation_id` varchar(32) NOT NULL, `refund_description` varchar(100) NOT NULL, `amount` decimal(10,2) NOT NULL, `comment` varchar(100) NOT NULL,`date_created` datetime NOT NULL, PRIMARY KEY (`id`)) ENGINE='.
            _MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'
        );
    }

    /**
     * Simplify Commerce's module uninstalling. Remove the config values and delete the tables.
     *
     * @return boolean Uninstall result
     */
    public function uninstall()
    {
        $this->uninstallTab();

        return parent::uninstall()
            && Configuration::deleteByName('SIMPLIFY_MODE')
            && Configuration::deleteByName('SIMPLIFY_SAVE_CUSTOMER_DETAILS')
            && Configuration::deleteByName('SIMPLIFY_PUBLIC_KEY_TEST')
            && Configuration::deleteByName('SIMPLIFY_PUBLIC_KEY_LIVE')
            && Configuration::deleteByName('SIMPLIFY_PRIVATE_KEY_TEST')
            && Configuration::deleteByName('SIMPLIFY_PRIVATE_KEY_LIVE')
            && Configuration::deleteByName('SIMPLIFY_PAYMENT_ORDER_STATUS')
            && Configuration::deleteByName('SIMPLIFY_OVERLAY_COLOR')
            && Configuration::deleteByName('SIMPLIFY_PAYMENT_TITLE')
            && Configuration::deleteByName('SIMPLIFY_TXN_MODE')
            && Configuration::deleteByName('SIMPLIFY_PAYMENT_OPTION')
            && Db::getInstance()->Execute('DROP TABLE IF EXISTS`'._DB_PREFIX_.'simplify_customer`')
            && $this->unregisterHook('paymentOptions')
            && $this->unregisterHook('orderConfirmation')
            && $this->unregisterHook('displayHeader')
            && $this->unregisterHook('displayAdminOrderLeft');
    }

    /**
     * @return void
     */
    public function initSimplify()
    {
        include(dirname(__FILE__).'/lib/Simplify.php');

        $api_keys = $this->getSimplifyAPIKeys();
        Simplify::$publicKey = $api_keys->public_key;
        Simplify::$privateKey = $api_keys->private_key;
    }

    /**
     * Display the Simplify Commerce's payment form
     *
     * @return string[]|bool Simplify Commerce's payment form
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return false;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->initSimplify();

        // If flag checked in the settings, look up customer details in the DB
        $isTokenizationEnabled = (bool)Configuration::get('SIMPLIFY_SAVE_CUSTOMER_DETAILS');
        $isLogged = $this->context->customer->isLogged();
        if ($isTokenizationEnabled && $isLogged) {
            $this->smarty->assign('show_save_customer_details_checkbox', true);
            $simplify_customer_id = Db::getInstance()->getValue(
                'SELECT simplify_customer_id FROM '.
                _DB_PREFIX_.'simplify_customer WHERE customer_id = '.(int)$this->context->cookie->id_customer
            );

            if ($simplify_customer_id) {
                // look up the customer's details
                try {
                    $customer = Simplify_Customer::findCustomer($simplify_customer_id);
                    $this->smarty->assign('show_saved_card_details', true);
                    $this->smarty->assign('customer_details', $customer);
                } catch (Simplify_ApiException $e) {
                    if (class_exists('Logger')) {
                        Logger::addLog(
                            $this->l('Simplify Commerce - Error retrieving customer'),
                            1,
                            null,
                            'Cart',
                            (int)$this->context->cart->id,
                            true
                        );
                    }

                    if ($e->getErrorCode() == 'object.not.found') {
                        $this->deleteCustomerFromDB();
                    } // remove the old customer from the database, as it no longer exists in Simplify
                }
            }
        }

        $cardholder_details = $this->getCardholderDetails();

        $currency = new Currency((int)($this->context->cart->id_currency));

        // Set js variables to send in card tokenization
        $this->smarty->assign('simplify_public_key', Simplify::$publicKey);

        $this->smarty->assign('customer_name',
            sprintf(
                '%s %s',
                $this->safe($cardholder_details->firstname),
                $this->safe($cardholder_details->lastname)
            )
        );
        $this->smarty->assign('firstname', $this->safe($cardholder_details->firstname));
        $this->smarty->assign('lastname', $this->safe($cardholder_details->lastname));
        $this->smarty->assign('city', $this->safe($cardholder_details->city));
        $this->smarty->assign('address1', $this->safe($cardholder_details->address1));
        $this->smarty->assign('address2', $this->safe($cardholder_details->address2));
        $this->smarty->assign(
            'state',
            isset($cardholder_details->state) ? $this->safe($cardholder_details->state) : ''
        );
        $this->smarty->assign('postcode', $this->safe($cardholder_details->postcode));

        //fields related to hosted payments
        $this->smarty->assign('hosted_payment_name', $this->safe($this->context->shop->name));
        $this->smarty->assign(
            'hosted_payment_description',
            $this->safe($this->context->shop->name).$this->l(' Order Number: ').(int)$this->context->cart->id
        );
        $this->smarty->assign('hosted_payment_reference', 'Order Number'.(int)$this->context->cart->id);
        $this->smarty->assign('hosted_payment_amount', ($this->context->cart->getOrderTotal() * 100));

        $this->smarty->assign(
            'overlay_color',
            Configuration::get('SIMPLIFY_OVERLAY_COLOR') != null ? Configuration::get(
                'SIMPLIFY_OVERLAY_COLOR'
            ) : $this->defaultModalOverlayColor
        );

        $this->smarty->assign('module_dir', $this->_path);
        $this->smarty->assign('currency_iso', $currency->iso_code);

        $options = [];
        if (!Configuration::get('SIMPLIFY_ENABLED_PAYMENT_WINDOW')) {
            return $options;
        }

        if (Configuration::get('SIMPLIFY_PAYMENT_OPTION') === self::PAYMENT_OPTION_EMBEDDED) {
            $this->smarty->assign('enabled_payment_window', 0);
            $this->smarty->assign('enabled_embedded', 1);
            $options[] = $this->getEmbeddedPaymentOption();
        } else {
            $this->smarty->assign('enabled_payment_window', 1);
            $this->smarty->assign('enabled_embedded', 0);
            $options[] = $this->getPaymentOption();
        }

        return $options;
    }

    protected function safe($field)
    {
        $copy = $field;
        $encoding = mb_detect_encoding($field);
        if ($encoding !== 'ASCII') {
            if (function_exists('transliterator_transliterate')) {
                $field = transliterator_transliterate('Any-Latin; Latin-ASCII', $field);
            } else {
                if (function_exists('iconv')) {
                    // fall back to iconv if intl module not available
                    $field = iconv($encoding, 'ASCII//TRANSLIT//IGNORE', $field);
                    $field = str_ireplace('?', '', $field);
                    $field = trim($field);
                } else {
                    // no transliteration possible, revert to original field
                    return $field;
                }
            }
            if (!$field) {
                // if translit turned the string into any false-like value, return original instead
                return $copy;
            }
        }

        return $field;
    }

    public function getPaymentOption()
    {
        $option = new PaymentOption();
        $option
            ->setCallToActionText(Configuration::get('SIMPLIFY_PAYMENT_TITLE') ?: $this->defaultTitle)
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setModuleName('simplifycommerce')
            ->setForm($this->fetch('module:simplifycommerce/views/templates/front/payment.tpl'));

        return $option;
    }

    public function getEmbeddedPaymentOption()
    {
        $option = new PaymentOption();
        $option
            ->setCallToActionText(Configuration::get('SIMPLIFY_PAYMENT_TITLE') ?: $this->defaultTitle)
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setModuleName('simplifycommerce_embedded')
            ->setForm($this->fetch('module:simplifycommerce/views/templates/front/embedded-payment.tpl'));

        return $option;
    }

    /**
     * Display a confirmation message after an order has been placed.
     *
     * @param array $params Hook parameters
     *
     * @return string Simplify Commerce's payment confirmation screen
     */
    public function hookOrderConfirmation($params)
    {
        if (!isset($params['objOrder']) || ($params['objOrder']->module != $this->name)) {
            return false;
        }

        if ($params['objOrder'] && Validate::isLoadedObject($params['objOrder']) && isset($params['objOrder']->valid)) {
            $order = array(
                'reference' => $params['objOrder']->reference ?? sprintf('#%06d', $params['objOrder']->id),
                'valid'     => $params['objOrder']->valid,
            );
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

        // Extract POST parameters from the request
        $simplify_token_post = Tools::getValue('simplifyToken');
        $delete_customer_card_post = Tools::getValue('deleteCustomerCard');
        $save_customer_post = Tools::getValue('saveCustomer');

        Logger::addLog(
            $this->l('Simplify Commerce - Save Customer = '.$save_customer_post),
            1,
            null,
            'Cart',
            (int)$this->context->cart->id,
            true
        );

        $charge_customer_card = Tools::getValue('chargeCustomerCard');

        $token = !empty($simplify_token_post) ? $simplify_token_post : null;
        $should_delete_customer = !empty($delete_customer_card_post) ? $delete_customer_card_post : false;
        $should_save_customer = !empty($save_customer_post) ? $save_customer_post : false;
        $should_charge_customer_card = !empty($charge_customer_card) ? $charge_customer_card : false;

        include(dirname(__FILE__).'/lib/Simplify.php');
        $api_keys = $this->getSimplifyAPIKeys();
        Simplify::$publicKey = $api_keys->public_key;
        Simplify::$privateKey = $api_keys->private_key;

        // look up the customer
        $simplify_customer = Db::getInstance()->getRow(
            '
            SELECT simplify_customer_id FROM '._DB_PREFIX_.'simplify_customer
            WHERE customer_id = '.(int)$this->context->cookie->id_customer
        );

        $simplify_customer_id = $this->getSimplifyCustomerID($simplify_customer['simplify_customer_id']);

        // The user has chosen to delete the card, so we need to delete the customer
        if (isset($simplify_customer_id) && $should_delete_customer) {
            try {
                // delete on simplify.com
                $customer = Simplify_Customer::findCustomer($simplify_customer_id);
                $customer->deleteCustomer();
            } catch (Simplify_ApiException $e) {
                // can't find the customer on Simplify, so no need to delete
                if (class_exists('Logger')) {
                    Logger::addLog(
                        $this->l('Simplify Commerce - Error retrieving customer'),
                        1,
                        null,
                        'Cart',
                        (int)$this->context->cart->id,
                        true
                    );
                }
            }

            $this->deleteCustomerFromDB();
            $simplify_customer_id = null;
        }

        // The user has chosen to save the card details
        if ($should_save_customer == 'on') {
            Logger::addLog(
                $this->l('Simplify Commerce - $should_save_customer = '.$should_save_customer),
                1,
                null,
                'Cart',
                (int)$this->context->cart->id,
                true
            );
            // Customer exists already so update the card details from the card token
            if (isset($simplify_customer_id)) {
                try {
                    $customer = Simplify_Customer::findCustomer($simplify_customer_id);
                    $customer->deleteCustomer();
                    $this->deleteCustomerFromDB();
                    $simplify_customer_id = $this->createNewSimplifyCustomer($token);
                } catch (Simplify_ApiException $e) {
                    if (class_exists('Logger')) {
                        Logger::addLog(
                            $this->l('Simplify Commerce - Error updating customer card details'),
                            1,
                            null,
                            'Cart',
                            (int)$this->context->cart->id,
                            true
                        );
                    }
                }
            } else {
                $simplify_customer_id = $this->createNewSimplifyCustomer(
                    $token
                ); // Create a new customer from the card token
            }
        }

        $charge = (float)$this->context->cart->getOrderTotal();

        $payment_status = null;
        try {
            $amount = $charge * 100; // Cart total amount
            $amount = number_format($amount);
            $description = $this->context->shop->name.$this->l(' Order Number: ').(int)$this->context->cart->id;

            if (isset($simplify_customer_id) && ($should_charge_customer_card == 'true' || $should_save_customer == 'on')) {
                $requestData = array(
                    'amount'      => $amount,
                    'customer'    => $simplify_customer_id, // Customer stored in the database
                    'description' => $description,
                    'currency'    => $currency_order->iso_code,
                );
            } else {
                $requestData = array(
                    'amount'      => $amount,
                    'token'       => $token, // Token returned by Simplify Card Tokenization
                    'description' => $description,
                    'currency'    => $currency_order->iso_code,
                );
            }

            $txn_mode = Configuration::get('SIMPLIFY_TXN_MODE');

            if ($txn_mode === self::TXN_MODE_PURCHASE) {
                $simplify_payment = Simplify_Payment::createPayment($requestData);
            } else {
                if ($txn_mode === self::TXN_MODE_AUTHORIZE) {
                    $simplify_payment = Simplify_Authorization::createAuthorization($requestData);
                }
            }

            $payment_status = $simplify_payment->paymentStatus;
        } catch (Simplify_ApiException $e) {
            $this->failPayment($e->getMessage());
        }

        if ($payment_status != 'APPROVED') {
            $this->failPayment(
                sprintf(
                    "The payment was %s",
                    $payment_status
                )
            );
        }

        // Log the transaction
        $message = $this->l('Simplify Commerce Transaction Details:').'\n\n'.
                   $this->l('Payment ID:').' '.$simplify_payment->id.'\n'.
                   $this->l('Payment Status:').' '.$simplify_payment->paymentStatus.'\n'.
                   $this->l('Amount:').' '.$simplify_payment->amount * 0.01 .'\n'.
                   $this->l('Currency:').' '.$simplify_payment->currency.'\n'.
                   $this->l('Description:').' '.$simplify_payment->description.'\n'.
                   $this->l('Auth Code:').' '.$simplify_payment->authCode.'\n'.
                   $this->l('Fee:').' '.$simplify_payment->fee * 0.01 .'\n'.
                   $this->l('Card Last 4:').' '.$simplify_payment->card->last4.'\n'.
                   $this->l('Card Expiry Year:').' '.$simplify_payment->card->expYear.'\n'.
                   $this->l('Card Expiry Month:').' '.$simplify_payment->card->expMonth.'\n'.
                   $this->l('Card Type:').' '.$simplify_payment->card->type.'\n';

        // Create the PrestaShop order in database
        $newStatus = ($txn_mode === self::TXN_MODE_AUTHORIZE)
            ? (int)Configuration::get('SIMPLIFY_OS_AUTHORIZED')
            : (int)Configuration::get('SIMPLIFY_PAYMENT_ORDER_STATUS');

        $this->validateOrder(
            (int)$this->context->cart->id,
            $newStatus,
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
                    $payment_card = $simplify_payment->card;
                    if ($payment_card) {
                        $payment[0]->card_number = pSQL($payment_card->last4);
                        $payment[0]->card_brand = pSQL($payment_card->type);
                        $payment[0]->card_expiration = sprintf(
                            "%s/%s",
                            pSQL($payment_card->expMonth),
                            pSQL($payment_card->expYear)
                        );
                        $payment[0]->card_holder = pSQL($payment_card->name);
                    }
                    $payment[0]->save();
                }
            }
        }

        if (Configuration::get('SIMPLIFY_MODE')) {
            Configuration::updateValue('SIMPLIFYCOMMERCE_CONFIGURED', true);
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            Tools::redirect(
                Link::getPageLink('order-confirmation.php', null, null).
                '?id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->id.'&id_order='.
                (int)$this->currentOrder.'&key='.$this->context->customer->secure_key,
                ''
            );
        } else {
            Tools::redirect(
                $this->context->link->getPagelink(
                    'order-confirmation.php',
                    null,
                    null,
                    array(
                        'id_cart'   => (int)$this->context->cart->id,
                        'id_module' => (int)$this->id,
                        'id_order'  => (int)$this->currentOrder,
                        'key'       => $this->context->customer->secure_key,
                    )
                )
            );
        }
        exit;
    }

    /**
     * @return Address|stdClass
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getCardholderDetails()
    {
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

        return $cardholder_details;
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
            $customer = Simplify_Customer::findCustomer($customer_id);
            $simplify_customer_id = $customer->id;
        } catch (Simplify_ApiException $e) {
            // can't find the customer on Simplify, so no need to delete
            if (class_exists('Logger')) {
                Logger::addLog(
                    $this->l('Simplify Commerce - Error retrieving customer'),
                    1,
                    null,
                    'Cart',
                    (int)$this->context->cart->id,
                    true
                );
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
        Db::getInstance()->Execute(
            'DELETE FROM '._DB_PREFIX_.'simplify_customer WHERE customer_id = '.(int)$this->context->cookie->id_customer.';'
        );
    }

    /**
     * Function to create a new Simplify customer and to store its id in the database.
     *
     * @param $token
     *
     * @return string Simplify customer's id.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createNewSimplifyCustomer($token)
    {
        try {
            $customer = Simplify_Customer::createCustomer(
                array(
                    'email'     => (string)$this->context->cookie->email,
                    'name'      => (string)$this->context->cookie->customer_firstname.' '.(string)$this->context->cookie->customer_lastname,
                    'token'     => $token,
                    'reference' => sprintf(
                        "%s %d",
                        $this->context->shop->name,
                        (int)$this->context->cookie->id_customer
                    ),
                )
            );

            $simplify_customer_id = pSQL($customer->id);

            Db::getInstance()->Execute(
                '
                INSERT INTO '._DB_PREFIX_.'simplify_customer (id, customer_id, simplify_customer_id, date_created)
                VALUES (NULL, '.(int)$this->context->cookie->id_customer.', \''.$simplify_customer_id.'\', NOW())'
            );
        } catch (Simplify_ApiException $e) {
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
            Logger::addLog(
                $this->l('Simplify Commerce - Payment transaction failed').' '.$message,
                1,
                null,
                'Cart',
                (int)$this->context->cart->id,
                true
            );
        }

        $controller = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc.php' : 'order.php';
        error_log($message);
        $location = sprintf(
            "%s%sstep=3&simplify_error=There was a problem with your payment: %s.#simplify_error",
            $this->context->link->getPageLink($controller),
            strpos($controller, '?') !== false ? '&' : '?',
            $message
        );
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
            return Configuration::get('SIMPLIFY_PUBLIC_KEY_LIVE') != '' && Configuration::get(
                    'SIMPLIFY_PRIVATE_KEY_LIVE'
                ) != '';
        } else {
            return Configuration::get('SIMPLIFY_PUBLIC_KEY_TEST') != '' && Configuration::get(
                    'SIMPLIFY_PRIVATE_KEY_TEST'
                ) != '';
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
            'name'   => $this->l('PHP cURL extension must be enabled on your server'),
            'result' => extension_loaded('curl'),
        );

        if (Configuration::get('SIMPLIFY_MODE')) {
            $tests['ssl'] = array(
                'name'   => $this->l('SSL must be enabled on your store (before entering Live mode)'),
                'result' => Configuration::get('PS_SSL_ENABLED') || (!empty($_SERVER['HTTPS']) && Tools::strtolower(
                            $_SERVER['HTTPS']
                        ) != 'off'),
            );
        }

        $tests['php52'] = array(
            'name'   => $this->l('Your server must run PHP 5.3 or greater'),
            'result' => version_compare(PHP_VERSION, '5.3.0', '>='),
        );

        $tests['configuration'] = array(
            'name'   => $this->l('You must set your Simplify Commerce API Keys'),
            'result' => $this->checkSettings(),
        );

        if ($tests['configuration']['result']) {
            $tests['keyprefix'] = array(
                'name'   => $this->l(
                    'Your API Keys appears to be invalid. Please make sure that you specified the right keys.'
                ),
                'result' => $this->checkKeyPrefix(),
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
                'SIMPLIFY_MODE'                   => Tools::getValue('simplify_mode'),
                'SIMPLIFY_SAVE_CUSTOMER_DETAILS'  => Tools::getValue('simplify_save_customer_details'),
                'SIMPLIFY_PUBLIC_KEY_TEST'        => Tools::getValue('simplify_public_key_test'),
                'SIMPLIFY_PUBLIC_KEY_LIVE'        => Tools::getValue('simplify_public_key_live'),
                'SIMPLIFY_PRIVATE_KEY_TEST'       => Tools::getValue('simplify_private_key_test'),
                'SIMPLIFY_PRIVATE_KEY_LIVE'       => Tools::getValue('simplify_private_key_live'),
                'SIMPLIFY_ENABLED_PAYMENT_WINDOW' => Tools::getValue('simplify_enabled_payment_window'),
                'SIMPLIFY_PAYMENT_ORDER_STATUS'   => (int)Tools::getValue('simplify_payment_status'),
                'SIMPLIFY_OVERLAY_COLOR'          => Tools::getValue('simplify_overlay_color'),
                'SIMPLIFY_PAYMENT_TITLE'          => Tools::getValue('simplify_payment_title'),
                'SIMPLIFY_TXN_MODE'               => Tools::getValue('simplify_txn_mode'),
                'SIMPLIFY_PAYMENT_OPTION'         => Tools::getValue('simplify_payment_option'),
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
        $this->smarty->assign('enabled_payment_window', Configuration::get('SIMPLIFY_ENABLED_PAYMENT_WINDOW'));
        $this->smarty->assign('enabled_embedded', Configuration::get('SIMPLIFY_ENABLED_EMBEDDED'));
        $this->smarty->assign('save_customer_details', Configuration::get('SIMPLIFY_SAVE_CUSTOMER_DETAILS'));
        $this->smarty->assign('statuses', OrderState::getOrderStates((int)$this->context->cookie->id_lang));
        $this->smarty->assign('request_uri', Tools::safeOutput($_SERVER['REQUEST_URI']));
        $this->smarty->assign(
            'overlay_color',
            Configuration::get('SIMPLIFY_OVERLAY_COLOR') != null ? Configuration::get(
                'SIMPLIFY_OVERLAY_COLOR'
            ) : $this->defaultModalOverlayColor
        );
        $this->smarty->assign('payment_title', Configuration::get('SIMPLIFY_PAYMENT_TITLE') ?: $this->defaultTitle);
        $this->smarty->assign(
            'embedded_payment_title',
            Configuration::get('SIMPLIFY_EMBEDDED_PAYMENT_TITLE') ?: $this->defaultTitle
        );
        $this->smarty->assign('txn_mode', Configuration::get('SIMPLIFY_TXN_MODE') ?: self::TXN_MODE_PURCHASE);
        $this->smarty->assign(
            'txn_mode_options',
            array(
                array(
                    'label' => $this->l('Payment'),
                    'value' => self::TXN_MODE_PURCHASE,
                ),
                array(
                    'label' => $this->l('Authorize'),
                    'value' => self::TXN_MODE_AUTHORIZE,
                ),
            )
        );
        $this->smarty->assign(
            'payment_option',
            Configuration::get('SIMPLIFY_PAYMENT_OPTION') ?: self::PAYMENT_OPTION_EMBEDDED
        );
        $this->smarty->assign(
            'payment_options',
            array(
                array(
                    'label' => $this->l('Embedded Payment Form'),
                    'value' => self::PAYMENT_OPTION_EMBEDDED,
                ),
                array(
                    'label' => $this->l('Modal Payment Window'),
                    'value' => self::PAYMENT_OPTION_MODAL,
                ),
            )
        );
        $this->smarty->assign(
            'statuses_options',
            array(
                array(
                    'name'          => 'simplify_payment_status',
                    'label'         => $this->l('Successful Payment Order Status'),
                    'current_value' => Configuration::get('SIMPLIFY_PAYMENT_ORDER_STATUS'),
                ),
            )
        );

        $base_img = $this->context->link->getBaseLink().'modules/'.$this->name.'/views/img/';

        $this->smarty->assign('ok_icon_link', $base_img.'checkmark-24.ico');
        $this->smarty->assign('nok_icon_link', $base_img.'x-mark-24.ico');

        $html .= $this->display(__FILE__, 'views/templates/hook/module-wrapper.tpl');

        return $html;
    }
}

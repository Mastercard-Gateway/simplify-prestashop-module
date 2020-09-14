<?php
/**
 * Copyright (c) 2019-2020 Mastercard
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

class AdminSimplifyController extends ModuleAdminController
{
    /**
     * @return bool|ObjectModel|void
     * @throws Exception
     */
    public function postProcess()
    {
        $action = Tools::getValue('action');
        if (!$action) {
            return;
        }

        $actionName = $action . 'Action';
        $orderId = Tools::getValue('id_order');
        $order = new Order($orderId);

        $this->module->initSimplify();

        try {
            $this->{$actionName}($order);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders').'&conf=4&id_order='.(int)$order->id.'&vieworder');
        } catch (Simplify_ApiException $e) {
            $this->errors[] = $e->describe();
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage() . ' (' . $e->getCode() . ')';
        }

        parent::postProcess();
    }

    /**
     * @param Order $order
     * @return void
     * @throws Exception
     */
    protected function captureAction($order)
    {
        $payment = $order->getOrderPaymentCollection()->getFirst();
        $txnId = $payment->transaction_id;

        $paymentAmount = number_format($payment->amount, 2);
        $paidAmount = number_format($order->total_paid, 2);

        if ($paymentAmount !== $paidAmount) {
            throw new Exception('Transaction paid amount does not equal order amount.');
        }

        $currency_order = new Currency((int)($order->id_currency));

        $auth = Simplify_Authorization::findAuthorization($txnId);
        if ($auth->amount !== (int)($payment->amount * 100)) {
            throw new Exception('Authorized amount does not equal paid amount.');
        }

        $payment = Simplify_Payment::createPayment([
            'authorization' => $auth->id,
            'currency'      => strtoupper( $currency_order->iso_code ),
            'amount'        => $auth->amount
        ]);

        $this->updateOrder(
            $auth->id,
            $order
        );
    }

    /**
     * @param Order $order
     * @return void
     */
    protected function voidAction($order)
    {
        $payment = $order->getOrderPaymentCollection()->getFirst();
        $txnId = $payment->transaction_id;

        $auth = Simplify_Authorization::findAuthorization($txnId);
        $auth->deleteAuthorization();

        $this->updateOrder(
            $auth->id,
            $order
        );
    }

    /**
     * @param string $paymentId
     * @param Order $order
     */
    protected function updateOrder($paymentId, $order)
    {
        $newStatus = null;
        $payment = Simplify_Authorization::findAuthorization($paymentId);

        if ($payment->reversed === true) {
            $newStatus = Configuration::get('PS_OS_CANCELED');
        }

        if ($payment->captured === true) {
            $newStatus = Configuration::get('SIMPLIFY_PAYMENT_ORDER_STATUS');
        }

        if ($newStatus !== null) {
            $history = new OrderHistory();
            $history->id_order = (int)$order->id;
            $history->changeIdOrderState($newStatus, $order, true);
            $history->addWithemail(true, array());
        }
    }
}

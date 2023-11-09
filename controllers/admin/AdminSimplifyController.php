<?php
/**
 * Copyright (c) 2019-2023 Mastercard
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
        $orderId = $order->id;
        $paymentAmount = number_format($payment->amount, 2);
        $paidAmount = number_format($order->total_paid, 2);

        if ($paymentAmount !== $paidAmount) {
            throw new Exception('Transaction paid amount does not equal order amount.');
        }

        $currency_order = new Currency((int)($order->id_currency));

        $auth = Simplify_Authorization::findAuthorization($txnId);

        if ($auth->amount !== (int) round($payment->amount * 100)) {
            throw new Exception('Authorized amount does not equal paid amount.');
        }

        $payment = Simplify_Payment::createPayment([
            'authorization' => $auth->id,
            'currency'      => strtoupper( $currency_order->iso_code ),
            'amount'        => $auth->amount
        ]);

        if($payment->declineReason === "AUTHORIZATION_EXPIRED"){
            $comment = "System error : Authorization expired for this order";
            $this->insertCapture($payment,$orderId,$comment);
            $newStatus = Configuration::get('PS_OS_CANCELED'); 
            if ($newStatus !== null) {
            $history = new OrderHistory();
            $history->id_order = (int)$order->id;
            $history->changeIdOrderState($newStatus, $order, true);
            $history->addWithemail(true, array());  
            throw new Exception('Transaction Declined.');
        }
              
        }elseif($payment->paymentStatus === "DECLINED"){
            $comment = "Transaction failed.";
            $this->insertCapture($payment,$orderId,$comment);
            throw new Exception('Transaction Failed.');
            
        }else{
            $comment = "Transaction Successful";
            // Insert the capture details into the database
            $this->insertCapture($payment,$orderId,$comment);

            $this->updateOrder(
                $auth->id,
                $order
            );
        }
    }

    /**
     * @param Payment $payment
     * @return true
     * @throws Exception
     */
    private function insertCapture($payment, $orderId, $comment)
    {
        // Get the values of the fields that you want to insert into the database.
        $capture_transcation_id = $payment->authorization->id;
        $payment_transcation_id = $payment->id; 
        $amount = $payment->amount / 100; 
        $date = $payment->transactionData->date;

        // Build the SQL INSERT statement using the Db::getInstance()->insert method.
        $data = array(
            'order_id' => $orderId,
            'capture_transcation_id' => $capture_transcation_id,
            'payment_transcation_id' => $payment_transcation_id,
            'amount' => $amount,
            'comment' => $comment,
            'transcation_date' => $date,
        );
        if (!Db::getInstance()->insert('capture_table', $data)) {
            throw new Exception('Error inserting capture data into the database.');
        }
        return true; // Return true on success, or handle errors as needed.
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

    /**
     * @param string $orderReference
     *
     * @return string
     */
    private function getUniqueTransactionId($txnId)
    {
        $uniqId = substr(uniqid(), 7, 6);
        return sprintf('%s-%s', $txnId, $uniqId);
    }

    /**
     * Partial refund action for your module.
     * Handles partial refunds for an order.
     *
     * @return JSON
     */
    public function PartialRefundAction()
    {
        if (isset($_POST['action']) && $_POST['action'] === 'partialRefund') {
            $RefundAmount = ($_POST['RefundAmount']);
            $RefundReason = trim($_POST['Refundreason']);
            $OrderId = trim($_POST['OrderId']);
            $ProductAmount = trim($_POST['ProductAmount']);
            $tableName = 'orders';
            $columnName = 'reference';
            $order_id = 'id_order';

            $sql = 'SELECT ' . pSQL($columnName) . ' FROM ' . pSQL(_DB_PREFIX_ . $tableName) . ' WHERE ' . pSQL($order_id) . ' = \'' . pSQL($OrderId) . '\'';
            $reference_id = Db::getInstance()->getValue($sql);

            // Get the transaction ID from the order_payment table
            $tableName = 'order_payment';
            $columnName = 'transaction_id';
            $order_reference = 'order_reference';

            $sql = 'SELECT ' . pSQL($columnName) . ' FROM ' . pSQL(_DB_PREFIX_ . $tableName) . ' WHERE ' . pSQL($order_reference) . ' = \'' . pSQL($reference_id) . '\'';
            $transaction_id = Db::getInstance()->getValue($sql);

            // Get the payment transaction ID from the capture_table
            $tableName = 'capture_table';
            $columnName = 'payment_transcation_id';
            $capturename = 'capture_transcation_id';
            
            $sql = 'SELECT ' . pSQL($columnName) . ' FROM ' . pSQL(_DB_PREFIX_ . $tableName) . ' WHERE ' . pSQL($capturename) . ' = \'' . pSQL($transaction_id) . '\'';
            $payment_transaction_id = Db::getInstance()->getValue($sql);

            // Determine the value to use
            if ($payment_transaction_id) {
                // Use the payment transaction ID if it exists
                $valueToUse = $payment_transaction_id;
            } else {
                // Use the capture transaction ID if payment transaction ID is not found
                $valueToUse = $transaction_id;
            }

            $newtxnId = $this->getUniqueTransactionId($transaction_id);
            $amount = number_format(($RefundAmount * 100),2);
            // Create a refund using the Simplify API
            $refund = Simplify_Refund::createRefund([
                'reference' => $newtxnId,
                'reason' => $RefundReason,
                'amount' => $amount,
                'payment' => $valueToUse,
            ]);

            if($refund->paymentStatus === "APPROVED"){
                $comment = "Transaction Successful";
                // Insert the refund details into the database
                $this->insertRefund($refund,$OrderId,$comment);
                $tableName = 'refund_table'; 
                $order_id = 'order_id'; 

                // Build the SQL query using pSQL
                $sql = 'SELECT ' . pSQL($order_id) . ', SUM(amount) AS total_amount FROM ' . pSQL(_DB_PREFIX_ . $tableName) . ' WHERE ' . pSQL($order_id) . ' = \'' . pSQL($OrderId) . '\' GROUP BY ' . pSQL($order_id);

                // Get the total_amount using Db::getInstance()->getValue()
                $result = Db::getInstance()->executeS($sql);
                $total_amount = $result[0]['total_amount'];

                // Check the refund status and update the order status accordingly
                $newStatus = null;
                $refundId = $refund->id;
                $payment = Simplify_Refund::findRefund($refundId);

                if($ProductAmount == $total_amount && $payment->paymentStatus === 'APPROVED'){

                    $newStatus = Configuration::get('PS_OS_REFUND');

                }
                if ($ProductAmount != $total_amount && $payment->paymentStatus === 'APPROVED') {
                    
                    $newStatus = Configuration::get('SIMPLIFY_OS_PARTIAL_REFUND');
                }

                if ($newStatus !== null) {
                    $history = new OrderHistory();
                    $history->id_order = (int)$OrderId;
                    $history->changeIdOrderState($newStatus, $OrderId, true);
                    $history->addWithemail(true, array());
                } 
      
                // Return JSON response
                echo json_encode('{"status":"success"}');
                exit;
            }else{
                // Insert the refund details into the database
                $comment = "Transaction Failed";
                $this->insertRefund($refund,$OrderId,$comment);
                echo json_encode('{"status":"failed"}');
                exit;
            }
        }
    }

    /**
     * Full refund action for your module.
     * Handles Full refunds for an order.
     *
     * @return JSON 
     */
    public function FullRefundAction()
    {
        if (isset($_POST['action']) && $_POST['action'] === 'fullRefund') {
            $RefundAmount = trim($_POST['RefundAmount']);
            $RefundReason = trim($_POST['Refundreason']);
            $OrderId = trim($_POST['OrderId']);

            $tableName = 'orders';
            $columnName = 'reference';
            $order_id = 'id_order';

            $sql = 'SELECT ' . pSQL($columnName) . ' FROM ' . pSQL(_DB_PREFIX_ . $tableName) . ' WHERE ' . pSQL($order_id) . ' = \'' . pSQL($OrderId) . '\'';
            $reference_id = Db::getInstance()->getValue($sql);

            // Get the transaction ID from the order_payment table
            $tableName = 'order_payment';
            $columnName = 'transaction_id';
            $order_reference = 'order_reference';

            $sql = 'SELECT ' . pSQL($columnName) . ' FROM ' . pSQL(_DB_PREFIX_ . $tableName) . ' WHERE ' . pSQL($order_reference) . ' = \'' . pSQL($reference_id) . '\'';
            $transaction_id = Db::getInstance()->getValue($sql);

            // Get the payment transaction ID from the capture_table
            $tableName = 'capture_table';
            $columnName = 'payment_transcation_id';
            $capturename = 'capture_transcation_id';
            

            $sql = 'SELECT ' . pSQL($columnName) . ' FROM ' . pSQL(_DB_PREFIX_ . $tableName) . ' WHERE ' . pSQL($capturename) . ' = \'' . pSQL($transaction_id) . '\'';
            $payment_transaction_id = Db::getInstance()->getValue($sql);

            // Determine the value to use
            if ($payment_transaction_id) {
                $valueToUse = $payment_transaction_id;
            } else {
                $valueToUse = $transaction_id;
            }

            $newtxnId = $this->getUniqueTransactionId($transaction_id);
            $amount = number_format(($RefundAmount * 100),2);
            // Create a refund using the Simplify API
            $refund = Simplify_Refund::createRefund([
                'reference' => $newtxnId,
                'reason' => $RefundReason,
                'amount' => $amount,
                'payment' => $valueToUse,
            ]);
            if($refund->paymentStatus === "APPROVED"){
                $comment = "Transaction Successful";
                // Insert the refund details into the database
                $this->insertRefund($refund,$OrderId,$comment);

                // Check the refund status and update the order status accordingly
                $newStatus = null;
                $refundId = $refund->id;
                $payment = Simplify_Refund::findRefund($refundId);

                if ($payment->paymentStatus === 'APPROVED') {
                    
                    $newStatus = Configuration::get('PS_OS_REFUND');
                }

                if ($newStatus !== null) {
                    $history = new OrderHistory();
                    $history->id_order = (int)$OrderId;
                    $history->changeIdOrderState($newStatus, $OrderId, true);
                    $history->addWithemail(true, array());
                } 
      
                // Return JSON response
               echo json_encode('{"status":"success"}');
                exit;
            }else{
                // Insert the refund details into the database
                $comment = "Transaction Failed";
                $this->insertRefund($refund,$OrderId,$comment);
                echo json_encode('{"status":"failed"}');
                exit;
            }
        } 
    }

    /**
     * @param Refund $refund
     * @return true
     * @throws Exception
     */
    private function insertRefund($refund, $OrderId, $comment)
    {
        // Get the values of the fields that you want to insert into the database.
        $refund_id = $refund->id;
        $transcation_id = $refund->payment->id; // Use pSQL to sanitize input.
        $amount = $refund->amount / 100; 
        $refund_description = $refund->description;
        $date_created = $refund->dateCreated;
        $timestamp = $date_created / 1000; // Convert milliseconds to seconds
        $date = date("Y-m-d H:i:s", $timestamp); // Convert the timestamp to a readable date format
        // Build the SQL INSERT statement using the Db::getInstance()->insert method.
        $data = array(
            'refund_id' => $refund_id,
            'order_id'  => $OrderId,
            'transcation_id' => $transcation_id,
            'refund_description' => $refund_description,
            'amount' => $amount,
            'comment' => $comment,
            'date_created' => $date,
        );

        if (!Db::getInstance()->insert('refund_table', $data)) {
            throw new Exception('Error inserting refund data into the database.');
        }

        return true; // Return true on success, or handle errors as needed.
    }
}


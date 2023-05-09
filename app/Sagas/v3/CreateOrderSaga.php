<?php

namespace SDPMlab\AnserGateway\Sagas\v3;

use SDPMlab\AnserGateway\Services\v3\Order;
use SDPMlab\AnserGateway\Services\v3\Payment;
use SDPMlab\Anser\Orchestration\Saga\SimpleSaga;
use SDPMlab\AnserGateway\Services\v3\Inventory;
use SDPMlab\Anser\Service\ConcurrentAction;

class CreateOrderSaga extends SimpleSaga
{
    /**
     * 新增訂單補償
     *
     * @return void
     */
    public function orderCompensation()
    {
        $order    = new Order();
        $orderKey = $this->getOrchestrator()->orderKey;
        $userKey  = $this->getOrchestrator()->userKey;

        $order->deleteOrder($orderKey, $userKey)->do();
    }

    /**
     * 商品庫存補償
     *
     * @return void
     */
    public function productInventoryCompensateion()
    {
        $productInvArr    = $this->getOrchestrator()->productInvArr;
        $concurrentAction = new ConcurrentAction();
        $inventory = new Inventory();
        $orderKey  = $this->getOrchestrator()->orderKey;

        foreach ($productInvArr as $actionName => $productKey) {
            if ($this->getOrchestrator()->getStepAction($actionName)->isSuccess()) {
                $concurrentAction->addAction(
                    $actionName,
                    $inventory->addInventory(
                        $productKey,
                        $orderKey,
                        1,
                        'compensate')
                    );
            }
        }

        $concurrentAction->send();
    }

    /**
     * 付款補償
     *
     * @return void
     */
    public function paymentCompensation()
    {
        $createPaymentAction = $this->getOrchestrator()->getStepAction('createPayment');
        $error    = $createPaymentAction->getMeaningData();
        $orderKey = $this->getOrchestrator()->orderKey;
        $userKey  = $this->getOrchestrator()->userKey;

        $payment = new Payment();

        if ($error === 500) {
            $total = $this->getOrchestrator()->getStepAction('createOrder')->getMeaningData()['total'];
            $payment->deletePaymentByOrderKey($orderKey, $userKey, $total);
        }
    }
}
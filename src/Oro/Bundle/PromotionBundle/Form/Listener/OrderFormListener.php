<?php

namespace Oro\Bundle\PromotionBundle\Form\Listener;

use Symfony\Component\HttpFoundation\RequestStack;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;

class OrderFormListener
{
    const SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION = 'save_without_discounts_recalculation';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var AppliedDiscountManager
     */
    private $appliedDiscountManager;

    /**
     * @param RequestStack $requestStack
     * @param AppliedDiscountManager $appliedDiscountManager
     */
    public function __construct(
        RequestStack $requestStack,
        AppliedDiscountManager $appliedDiscountManager
    ) {
        $this->requestStack = $requestStack;
        $this->appliedDiscountManager = $appliedDiscountManager;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function beforeFlush(AfterFormProcessEvent $event)
    {
        $order = $event->getData();
        if (!$order instanceof Order || !$order->getId()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request &&
            $request->get(Router::ACTION_PARAMETER) === self::SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION
        ) {
            return;
        }

        $this->appliedDiscountManager->removeAppliedDiscountByOrder($order);
        $this->appliedDiscountManager->saveAppliedDiscounts($order);
    }
}

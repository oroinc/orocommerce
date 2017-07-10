<?php

namespace Oro\Bundle\PromotionBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;

class OrderTypeExtension extends AbstractTypeExtension
{
    const ON_SUBMIT_LISTENER_PRIORITY = 10;
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
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Should be called before OrderBundle\Form\Type\EventListener\SubtotalSubscriber::onSubmitEventListener
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit'], self::ON_SUBMIT_LISTENER_PRIORITY);
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
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

    /**
     * {@inheritDoc}
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }
}

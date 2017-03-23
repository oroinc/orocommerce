<?php

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DiscountSubscriber implements EventSubscriberInterface
{
    /**
     * @var TotalHelper
     */
    private $totalHelper;

    /**
     * @param TotalHelper $totalHelper
     */
    public function __construct(TotalHelper $totalHelper)
    {
        $this->totalHelper = $totalHelper;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'onSubmitEventListener',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmitEventListener(FormEvent $event)
    {
        $data = $event->getData();

        if (!$data instanceof OrderDiscount) {
            return;
        }

        $order = $data->getOrder();

        if (null === $order) {
            return;
        }

        $order->addDiscount($data);

        $this->totalHelper->fillDiscounts($order);
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\Order;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DiscountOrderApiProcessor implements ProcessorInterface
{
    /**
     * @var EventSubscriberInterface
     */
    private $eventSubscriber;

    /**
     * @param EventSubscriberInterface $eventSubscriber
     */
    public function __construct(EventSubscriberInterface $eventSubscriber)
    {
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return;
        }

        $context
            ->getFormBuilder()
            ->addEventSubscriber($this->eventSubscriber);
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Api\Form;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiOrderFormBuilderTotalsProcessor implements ProcessorInterface
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
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */
        if (false === $context->hasFormBuilder()) {
            return;
        }

        if ($context->hasForm()) {
            // the form is already built
            return;
        }

        $context
            ->getFormBuilder()
            ->addEventSubscriber($this->eventSubscriber);
    }
}

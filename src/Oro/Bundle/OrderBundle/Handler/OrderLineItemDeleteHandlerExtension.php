<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The delete handler extension for OrderLineItem entity.
 */
class OrderLineItemDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        /** @var OrderLineItem $entity */

        $order = $entity->getOrder();
        if (null === $order) {
            return;
        }

        if ($order->getLineItems()->count() === 1) {
            throw $this->createAccessDeniedException(
                $this->translator->trans('oro.order.orderlineitem.count', [], 'validators')
            );
        }
    }
}

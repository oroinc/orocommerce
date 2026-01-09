<?php

namespace Oro\Bundle\CheckoutBundle\Payment\Method\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEvent;

/**
 * Handles payment method renaming events to update checkout references.
 *
 * Listens to payment method renaming events and updates all checkouts that reference
 * the old payment method identifier to use the new identifier.
 */
class MethodRenamingListener
{
    /**
     * @var CheckoutRepository
     */
    private $checkoutRepository;

    public function __construct(CheckoutRepository $checkoutRepository)
    {
        $this->checkoutRepository = $checkoutRepository;
    }

    public function onMethodRename(MethodRenamingEvent $event)
    {
        $this->updateCheckouts($event->getOldMethodIdentifier(), $event->getNewMethodIdentifier());
    }

    /**
     * @param string $oldId
     * @param string $newId
     */
    private function updateCheckouts($oldId, $newId)
    {
        $configs = $this->checkoutRepository->findByPaymentMethod($oldId);
        foreach ($configs as $config) {
            $config->setPaymentMethod($newId);
        }
    }
}

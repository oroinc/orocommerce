<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Updates visibility for a customer when a customer is created, updated or removed.
 */
class CustomerListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var CustomerPartialUpdateDriverInterface */
    private $partialUpdateDriver;

    /**
     * @param MessageProducerInterface             $messageProducer
     * @param CustomerPartialUpdateDriverInterface $partialUpdateDriver
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        CustomerPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        $this->messageProducer = $messageProducer;
        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    /**
     * @param Customer $customer
     */
    public function postPersist(Customer $customer): void
    {
        if ($customer->getGroup()) {
            $this->sendMessageToProducer($customer);
        } else {
            $this->partialUpdateDriver->createCustomerWithoutCustomerGroupVisibility($customer);
        }
    }

    /**
     * @param Customer $customer
     */
    public function preRemove(Customer $customer): void
    {
        $this->partialUpdateDriver->deleteCustomerVisibility($customer);
    }

    /**
     * @param Customer           $customer
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Customer $customer, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('group')) {
            $this->sendMessageToProducer($customer);
        }
    }

    /**
     * @param Customer $customer
     */
    private function sendMessageToProducer(Customer $customer): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->messageProducer->send(Topics::CHANGE_CUSTOMER, ['id' => $customer->getId()]);
    }
}

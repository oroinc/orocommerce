<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeCustomerTopic;
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

    public function __construct(
        MessageProducerInterface $messageProducer,
        CustomerPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        $this->messageProducer = $messageProducer;
        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    public function postPersist(Customer $customer): void
    {
        if ($customer->getGroup()) {
            $this->sendMessageToProducer($customer);
        } else {
            $this->partialUpdateDriver->createCustomerWithoutCustomerGroupVisibility($customer);
        }
    }

    public function preRemove(Customer $customer): void
    {
        $this->partialUpdateDriver->deleteCustomerVisibility($customer);
    }

    public function preUpdate(Customer $customer, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('group')) {
            $this->sendMessageToProducer($customer);
        }
    }

    private function sendMessageToProducer(Customer $customer): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->messageProducer->send(VisibilityOnChangeCustomerTopic::getName(), ['id' => $customer->getId()]);
    }
}

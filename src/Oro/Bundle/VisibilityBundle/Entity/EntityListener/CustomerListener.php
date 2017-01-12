<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class CustomerListener
{
    /**
     * @var MessageFactoryInterface
     */
    protected $messageFactory;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var CustomerPartialUpdateDriverInterface
     */
    protected $partialUpdateDriver;

    /**
     * @var string
     */
    protected $topic = '';

    /**
     * @param MessageFactoryInterface $messageFactory
     * @param MessageProducerInterface $messageProducer
     * @param CustomerPartialUpdateDriverInterface $partialUpdateDriver
     */
    public function __construct(
        MessageFactoryInterface $messageFactory,
        MessageProducerInterface $messageProducer,
        CustomerPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageProducer = $messageProducer;
        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    /**
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = (string)$topic;
    }

    /**
     * @param Customer $customer
     */
    public function postPersist(Customer $customer)
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
    public function preRemove(Customer $customer)
    {
        $this->partialUpdateDriver->deleteCustomerVisibility($customer);
    }

    /**
     * @param Customer $customer
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Customer $customer, PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('group')) {
            $this->sendMessageToProducer($customer);
        }
    }

    /**
     * @param Customer $customer
     */
    protected function sendMessageToProducer(Customer $customer)
    {
        $this->messageProducer->send($this->topic, $this->messageFactory->createMessage($customer));
    }
}

<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\VisibilityBundle\Driver\AccountPartialUpdateDriverInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AccountListener
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
     * @var AccountPartialUpdateDriverInterface
     */
    protected $partialUpdateDriver;

    /**
     * @var string
     */
    protected $topic = '';

    /**
     * @param MessageFactoryInterface $messageFactory
     * @param MessageProducerInterface $messageProducer
     * @param AccountPartialUpdateDriverInterface $partialUpdateDriver
     */
    public function __construct(
        MessageFactoryInterface $messageFactory,
        MessageProducerInterface $messageProducer,
        AccountPartialUpdateDriverInterface $partialUpdateDriver
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
     * @param Customer $account
     */
    public function postPersist(Customer $account)
    {
        if ($account->getGroup()) {
            $this->sendMessageToProducer($account);
        } else {
            $this->partialUpdateDriver->createAccountWithoutAccountGroupVisibility($account);
        }
    }

    /**
     * @param Customer $account
     */
    public function preRemove(Customer $account)
    {
        $this->partialUpdateDriver->deleteAccountVisibility($account);
    }

    /**
     * @param Customer $account
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Customer $account, PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('group')) {
            $this->sendMessageToProducer($account);
        }
    }

    /**
     * @param Customer $account
     */
    protected function sendMessageToProducer(Customer $account)
    {
        $this->messageProducer->send($this->topic, $this->messageFactory->createMessage($account));
    }
}

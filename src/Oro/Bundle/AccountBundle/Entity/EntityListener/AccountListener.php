<?php

namespace Oro\Bundle\AccountBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Model\MessageFactoryInterface;
use Oro\Bundle\WebsiteSearchBundle\Driver\AccountPartialUpdateDriverInterface;
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
     * @param Account $account
     */
    public function postPersist(Account $account)
    {
        if ($account->getGroup()) {
            $this->sendMessageToProducer($account);
        } else {
            $this->partialUpdateDriver->createAccountWithoutAccountGroupVisibility($account);
        }
    }

    /**
     * @param Account $account
     */
    public function preRemove(Account $account)
    {
        $this->partialUpdateDriver->deleteAccountVisibility($account);
    }

    /**
     * @param Account $account
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Account $account, PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('group')) {
            $this->sendMessageToProducer($account);
        }
    }

    /**
     * @param Account $account
     */
    protected function sendMessageToProducer(Account $account)
    {
        $this->messageProducer->send($this->topic, $this->messageFactory->createMessage($account));
    }
}

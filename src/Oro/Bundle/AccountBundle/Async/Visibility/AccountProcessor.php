<?php

namespace Oro\Bundle\AccountBundle\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\AccountBundle\Model\MessageFactoryInterface;
use Oro\Bundle\WebsiteSearchBundle\Driver\AccountPartialUpdateDriverInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

use Psr\Log\LoggerInterface;

class AccountProcessor implements MessageProcessorInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MessageFactoryInterface
     */
    protected $messageFactory;

    /**
     * @var AccountPartialUpdateDriverInterface
     */
    protected $partialUpdateDriver;

    /**
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     * @param MessageFactoryInterface $messageFactory
     * @param AccountPartialUpdateDriverInterface $partialUpdateDriver
     */
    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger,
        MessageFactoryInterface $messageFactory,
        AccountPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->messageFactory = $messageFactory;
        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(BaseVisibilityResolved::class);
        $em->beginTransaction();
        try {
            $messageData = JSON::decode($message->getBody());
            /** @var Account $account */
            $account = $this->messageFactory->getEntityFromMessage($messageData);

            $this->partialUpdateDriver->updateAccountVisibility($account);
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $em->rollback();
            $this->logger->error(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    $e->getMessage(),
                    $message->getBody()
                )
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                sprintf(
                    'Transaction aborted wit error: %s.',
                    $e->getMessage()
                )
            );

            return self::REQUEUE;
        }

        return self::ACK;
    }
}

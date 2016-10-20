<?php

namespace Oro\Bundle\CustomerBundle\Async\Visibility;

use Oro\Bundle\CustomerBundle\Driver\AccountPartialUpdateDriverInterface;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\CustomerBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\CustomerBundle\Model\MessageFactoryInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

use Psr\Log\LoggerInterface;

class AccountProcessor implements MessageProcessorInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

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
     * @param DoctrineHelper $doctrineHelper
     * @param LoggerInterface $logger
     * @param MessageFactoryInterface $messageFactory
     * @param AccountPartialUpdateDriverInterface $partialUpdateDriver
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger,
        MessageFactoryInterface $messageFactory,
        AccountPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
        $this->messageFactory = $messageFactory;
        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $em = $this->doctrineHelper->getEntityManagerForClass(BaseVisibilityResolved::class);
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

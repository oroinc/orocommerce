<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerFactory;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates combined price lists in case of changes in structure of original price lists
 */
class CombinedPriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var CombinedPriceListsBuilderFacade
     */
    protected $combinedPriceListsBuilderFacade;

    /**
     * @var PriceListRelationTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CombinedPriceListTriggerHandler
     */
    private $triggerHandler;

    /**
     * @param LoggerInterface $logger
     * @param PriceListRelationTriggerFactory $triggerFactory
     * @param ManagerRegistry $registry
     * @param CombinedPriceListTriggerHandler $triggerHandler
     * @param CombinedPriceListsBuilderFacade $builderFacade
     */
    public function __construct(
        LoggerInterface $logger,
        PriceListRelationTriggerFactory $triggerFactory,
        ManagerRegistry $registry,
        CombinedPriceListTriggerHandler $triggerHandler,
        CombinedPriceListsBuilderFacade $builderFacade
    ) {
        $this->logger = $logger;
        $this->triggerFactory = $triggerFactory;
        $this->registry = $registry;
        $this->triggerHandler = $triggerHandler;
        $this->combinedPriceListsBuilderFacade = $builderFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CombinedPriceList::class);
        $em->beginTransaction();
        $this->triggerHandler->startCollect();

        try {
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);
            $this->handlePriceListRelationTrigger($trigger);
            $this->combinedPriceListsBuilderFacade->dispatchEvents();
            $this->triggerHandler->commit();
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $this->triggerHandler->rollback();
            $em->rollback();
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (\Exception $e) {
            $this->triggerHandler->rollback();
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Combined Price Lists build',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @param PriceListRelationTrigger $trigger
     */
    protected function handlePriceListRelationTrigger(PriceListRelationTrigger $trigger)
    {
        switch (true) {
            case !is_null($trigger->getCustomer()):
                $this->combinedPriceListsBuilderFacade->rebuildForCustomers(
                    [$trigger->getCustomer()],
                    $trigger->getWebsite(),
                    $trigger->isForce()
                );
                break;
            case !is_null($trigger->getCustomerGroup()):
                $this->combinedPriceListsBuilderFacade->rebuildForCustomerGroups(
                    [$trigger->getCustomerGroup()],
                    $trigger->getWebsite(),
                    $trigger->isForce()
                );
                break;
            case !is_null($trigger->getWebsite()):
                $this->combinedPriceListsBuilderFacade->rebuildForWebsites(
                    [$trigger->getWebsite()],
                    $trigger->isForce()
                );
                break;
            default:
                $this->combinedPriceListsBuilderFacade->rebuildAll($trigger->isForce());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::REBUILD_COMBINED_PRICE_LISTS];
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListCurrency;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Update supported currencies for Combined Price Lists by Price Lists.
 */
class CombinedPriceListCurrencyProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var PriceListTriggerFactory
     */
    private $triggerFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var CombinedPriceListProvider
     */
    private $combinedPriceListProvider;

    /**
     * @param LoggerInterface $logger
     * @param PriceListTriggerFactory $triggerFactory
     * @param ManagerRegistry $registry
     * @param CombinedPriceListProvider $combinedPriceListProvider
     */
    public function __construct(
        LoggerInterface $logger,
        PriceListTriggerFactory $triggerFactory,
        ManagerRegistry $registry,
        CombinedPriceListProvider $combinedPriceListProvider
    ) {
        $this->logger = $logger;
        $this->triggerFactory = $triggerFactory;
        $this->registry = $registry;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CombinedPriceListCurrency::class);
        $em->beginTransaction();

        try {
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);

            $priceListIds = $trigger->getPriceListIds();
            $cplRepository = $this->registry
                ->getManagerForClass(CombinedPriceList::class)
                ->getRepository(CombinedPriceList::class);

            $cpls = $cplRepository->getCombinedPriceListsByPriceLists($priceListIds);
            foreach ($cpls as $cpl) {
                $relations = $cplRepository->getPriceListRelations($cpl);
                $this->combinedPriceListProvider->actualizeCurrencies($cpl, $relations);
            }

            $em->flush();
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $em->rollback();
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (DeadlockException $e) {
            $em->rollback();
            $this->logger->error(
                'Deadlock exception occurred during Combined Price Lists currencies merging',
                ['exception' => $e]
            );

            return self::REQUEUE;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Combined Price Lists currencies merging',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_COMBINED_CURRENCIES];
    }
}

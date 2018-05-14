<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates combined price lists in case of price changes in some products
 */
class PriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var PriceListTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CombinedPriceListsBuilderFacade
     */
    protected $combinedPriceListsBuilderFacade;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DatabaseExceptionHelper
     */
    protected $databaseExceptionHelper;

    /**
     * @param PriceListTriggerFactory $triggerFactory
     * @param ManagerRegistry $registry
     * @param CombinedPriceListsBuilderFacade $combinedPriceListsBuilderFacade
     * @param LoggerInterface $logger
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     * @param CombinedPriceListTriggerHandler $triggerHandler
     */
    public function __construct(
        PriceListTriggerFactory $triggerFactory,
        ManagerRegistry $registry,
        CombinedPriceListsBuilderFacade $combinedPriceListsBuilderFacade,
        LoggerInterface $logger,
        DatabaseExceptionHelper $databaseExceptionHelper,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->triggerFactory = $triggerFactory;
        $this->registry = $registry;
        $this->combinedPriceListsBuilderFacade = $combinedPriceListsBuilderFacade;
        $this->logger = $logger;
        $this->databaseExceptionHelper = $databaseExceptionHelper;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CombinedPriceList::class);
        $em->beginTransaction();

        try {
            $this->triggerHandler->startCollect();
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);

            if ($trigger->getPriceList()) {
                /** @var CombinedPriceListRepository $cplRepository */
                $cplRepository = $this->getRepository(CombinedPriceList::class);
                $iterator = $cplRepository->getCombinedPriceListsByPriceList($trigger->getPriceList(), true);

                $this->combinedPriceListsBuilderFacade->rebuild($iterator, $trigger->getProducts());
            } else {
                /** @var CombinedPriceListToPriceListRepository $cpl2plRepository */
                $cpl2plRepository = $this->getRepository(CombinedPriceListToPriceList::class);
                $allProducts = $trigger->getProducts();

                $cpls = $cpl2plRepository->getCombinedPriceListsByActualPriceLists(array_keys($allProducts));
                foreach ($cpls as $cpl) {
                    $pls = $cpl2plRepository->getPriceListIdsByCpls([$cpl]);

                    $products = array_merge(...array_intersect_key($allProducts, array_flip($pls)));

                    $this->combinedPriceListsBuilderFacade->rebuild([$cpl], array_unique($products));
                }
            }
            $this->combinedPriceListsBuilderFacade->dispatchEvents();
            $em->commit();
            $this->triggerHandler->commit();
        } catch (InvalidArgumentException $e) {
            $em->rollback();
            $this->triggerHandler->rollback();
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->triggerHandler->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Combined Price Lists build',
                ['exception' => $e]
            );

            if ($e instanceof DriverException && $this->databaseExceptionHelper->isDeadlock($e)) {
                return self::REQUEUE;
            } else {
                return self::REJECT;
            }
        }

        return self::ACK;
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    private function getRepository($className)
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_COMBINED_PRICES];
    }
}

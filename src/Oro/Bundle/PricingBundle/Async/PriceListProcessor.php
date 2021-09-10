<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListActivationStatusHelperInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates combined price lists in case of price changes in some products.
 */
class PriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LoggerInterface */
    private $logger;

    /** @var CombinedPriceListsBuilderFacade */
    private $combinedPriceListsBuilderFacade;

    /** @var CombinedPriceListTriggerHandler */
    private $triggerHandler;

    /** @var CombinedPriceListActivationStatusHelperInterface */
    private $activationStatusHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        CombinedPriceListsBuilderFacade $combinedPriceListsBuilderFacade,
        CombinedPriceListTriggerHandler $triggerHandler,
        CombinedPriceListActivationStatusHelperInterface $activationStatusHelper
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->combinedPriceListsBuilderFacade = $combinedPriceListsBuilderFacade;
        $this->triggerHandler = $triggerHandler;
        $this->activationStatusHelper = $activationStatusHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_COMBINED_PRICES];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!isset($body['product']) || !\is_array($body['product'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(CombinedPriceList::class);
        $em->beginTransaction();
        try {
            $this->triggerHandler->startCollect();

            /** @var CombinedPriceListToPriceListRepository $cpl2plRepository */
            $cpl2plRepository = $em->getRepository(CombinedPriceListToPriceList::class);
            $allProducts = $body['product'];
            foreach ($this->getActiveCPlsByPls($cpl2plRepository, $allProducts) as $cpl) {
                $pls = $cpl2plRepository->getPriceListIdsByCpls([$cpl]);
                $products = array_merge(...array_intersect_key($allProducts, array_flip($pls)));
                $this->combinedPriceListsBuilderFacade->rebuild([$cpl], array_unique($products));
            }

            $this->combinedPriceListsBuilderFacade->dispatchEvents();
            $em->commit();
            $this->triggerHandler->commit();
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during Price Lists build.',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        } finally {
            if (isset($e)) {
                $em->rollback();
                $this->triggerHandler->rollback();
            }
        }

        return self::ACK;
    }

    private function getActiveCPlsByPls(
        CombinedPriceListToPriceListRepository $cpl2plRepository,
        array $allProducts
    ): iterable {
        $cpls = $cpl2plRepository->getCombinedPriceListsByActualPriceLists(array_keys($allProducts));
        foreach ($cpls as $cpl) {
            if ($this->activationStatusHelper->isReadyForBuild($cpl)) {
                yield $cpl;
            }
        }
    }
}

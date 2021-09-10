<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates supported currencies for combined price lists by price lists.
 */
class CombinedPriceListCurrencyProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LoggerInterface */
    private $logger;

    /** @var CombinedPriceListProvider */
    private $combinedPriceListProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        CombinedPriceListProvider $combinedPriceListProvider
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_COMBINED_CURRENCIES];
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
        $em = $this->doctrine->getManagerForClass(CombinedPriceListCurrency::class);
        $em->beginTransaction();
        try {
            $priceListIds = array_keys($body['product']);
            /** @var CombinedPriceListRepository $cplRepository */
            $cplRepository = $em->getRepository(CombinedPriceList::class);

            $cpls = $cplRepository->getCombinedPriceListsByPriceLists($priceListIds);
            foreach ($cpls as $cpl) {
                $relations = $cplRepository->getPriceListRelations($cpl);
                $this->combinedPriceListProvider->actualizeCurrencies($cpl, $relations);
            }

            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Combined Price Lists currencies merging.',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }
}

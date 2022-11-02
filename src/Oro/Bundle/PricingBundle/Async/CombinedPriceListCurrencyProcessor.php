<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceListCurrenciesTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Updates supported currencies for combined price lists by price lists.
 */
class CombinedPriceListCurrencyProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $doctrine;
    private CombinedPriceListProvider $combinedPriceListProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        CombinedPriceListProvider $combinedPriceListProvider
    ) {
        $this->doctrine = $doctrine;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ResolveCombinedPriceListCurrenciesTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();

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
            $this->logger?->error(
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

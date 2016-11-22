<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var PriceListTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ProductPriceBuilder
     */
    protected $priceResolver;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListRepository;

    /**
     * @var DatabaseExceptionHelper
     */
    protected $databaseExceptionHelper;

    /**
     * @param PriceListTriggerFactory $triggerFactory
     * @param ManagerRegistry $registry
     * @param CombinedProductPriceResolver $priceResolver
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     */
    public function __construct(
        PriceListTriggerFactory $triggerFactory,
        ManagerRegistry $registry,
        CombinedProductPriceResolver $priceResolver,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        DatabaseExceptionHelper $databaseExceptionHelper
    ) {
        $this->triggerFactory = $triggerFactory;
        $this->registry = $registry;
        $this->priceResolver = $priceResolver;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->databaseExceptionHelper = $databaseExceptionHelper;
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
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);
            $repository = $this->getCombinedPriceListRepository();
            $iterator = $repository->getCombinedPriceListsByPriceList(
                $trigger->getPriceList(),
                true
            );
            $builtCPLs = [];
            foreach ($iterator as $combinedPriceList) {
                $this->priceResolver->combinePrices($combinedPriceList, $trigger->getProduct());
                $builtCPLs[$combinedPriceList->getId()] = true;
            }
            if ($builtCPLs) {
                $this->dispatchEvent($builtCPLs);
            }
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
     * @param array $cplIds
     */
    protected function dispatchEvent(array $cplIds)
    {
        $event = new CombinedPriceListsUpdateEvent(array_keys($cplIds));
        $this->dispatcher->dispatch(CombinedPriceListsUpdateEvent::NAME, $event);
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRepository()
    {
        if (!$this->combinedPriceListRepository) {
            $this->combinedPriceListRepository = $this->registry
                ->getManagerForClass(CombinedPriceList::class)
                ->getRepository(CombinedPriceList::class);
        }

        return $this->combinedPriceListRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_COMBINED_PRICES];
    }
}

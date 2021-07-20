<?php

namespace Oro\Bundle\ProductBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Trigger update of search index for products with specified attribute
 */
class ReindexProductsByAttributesProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JobRunner */
    private $jobRunner;

    /** @var ManagerRegistry */
    private $registry;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        JobRunner $jobRunner,
        ManagerRegistry $registry,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        $this->jobRunner  = $jobRunner;
        $this->registry   = $registry;
        $this->dispatcher = $dispatcher;
        $this->logger     = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $body = JSON::decode($message->getBody());
            if (!isset($body['attributeIds'])) {
                throw new InvalidArgumentException();
            }
            $attributeIds = $body['attributeIds'];
            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::REINDEX_PRODUCTS_BY_ATTRIBUTES,
                function () use ($attributeIds) {
                    return $this->triggerReindex($attributeIds);
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $e,
                    'topic' => Topics::REINDEX_PRODUCTS_BY_ATTRIBUTES
                ]
            );

            return self::REJECT;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::REINDEX_PRODUCTS_BY_ATTRIBUTES];
    }

    /**
     * Trigger update search index only for products with off this attributes
     *
     * @param array $attributeIds
     * @return bool
     */
    private function triggerReindex(array $attributeIds)
    {
        try {
            /** @var ProductRepository $repository */
            $repository = $this->registry->getManagerForClass(Product::class)->getRepository(Product::class);

            $productIds = $repository->getProductIdsByAttributesId($attributeIds);
            if ($productIds) {
                $this->dispatcher->dispatch(
                    new ReindexationRequestEvent([Product::class], [], $productIds),
                    ReindexationRequestEvent::EVENT_NAME
                );
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during triggering update of search index ',
                [
                    'exception' => $e,
                    'topic' => Topics::REINDEX_PRODUCTS_BY_ATTRIBUTES
                ]
            );

            return false;
        }
    }
}

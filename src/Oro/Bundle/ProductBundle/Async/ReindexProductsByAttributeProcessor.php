<?php

namespace Oro\Bundle\ProductBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Trigger update of search index for products with specified attribute
 */
class ReindexProductsByAttributeProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JobRunner */
    private $jobRunner;

    /** @var ManagerRegistry */
    private $registry;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param JobRunner                $jobRunner
     * @param ManagerRegistry          $registry
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     */
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
            if (!isset($body['attributeId'])) {
                throw new InvalidArgumentException();
            }
            $attributeId = $body['attributeId'];
            $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::REINDEX_PRODUCTS_BY_ATTRIBUTE,
                function () use ($attributeId) {
                    return $this->triggerReindex($attributeId);
                }
            );

            return self::ACK;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $e,
                    'topic' => Topics::REINDEX_PRODUCTS_BY_ATTRIBUTE
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
        return [Topics::REINDEX_PRODUCTS_BY_ATTRIBUTE];
    }

    /**
     * Trigger update search index only for product with attribute
     *
     * @param string $attributeId
     */
    private function triggerReindex($attributeId)
    {
        /** @var ProductRepository $repository */
        $repository = $this->registry->getManagerForClass(Product::class)->getRepository(Product::class);

        $productIds = $repository->getProductIdsByAttributeId($attributeId);
        if (!$productIds) {
            return;
        }

        $this->dispatcher->dispatch(
            ReindexationRequestEvent::EVENT_NAME,
            new ReindexationRequestEvent([Product::class], [], $productIds)
        );
    }
}

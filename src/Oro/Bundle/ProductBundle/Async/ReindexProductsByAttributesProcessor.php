<?php

namespace Oro\Bundle\ProductBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductsByAttributesTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Trigger update of search index for products with specified attribute
 */
class ReindexProductsByAttributesProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private JobRunner $jobRunner;

    private ManagerRegistry $registry;

    private EventDispatcherInterface $dispatcher;

    public function __construct(
        JobRunner $jobRunner,
        ManagerRegistry $registry,
        EventDispatcherInterface $dispatcher
    ) {
        $this->jobRunner = $jobRunner;
        $this->registry = $registry;
        $this->dispatcher = $dispatcher;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            $body = $message->getBody();

            $attributeIds = $body['attributeIds'];

            $result = $this->jobRunner->runUniqueByMessage(
                $message,
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
                    'topic' => ReindexProductsByAttributesTopic::getName(),
                ]
            );

            return self::REJECT;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [ReindexProductsByAttributesTopic::getName()];
    }

    /**
     * Trigger update search index only for products with off this attributes
     *
     * @param array $attributeIds
     * @return bool
     */
    private function triggerReindex(array $attributeIds): bool
    {
        try {
            /** @var ProductRepository $repository */
            $repository = $this->registry
                ->getManagerForClass(Product::class)
                ?->getRepository(Product::class);

            $productIds = $repository->getProductIdsByAttributesId($attributeIds);
            if ($productIds) {
                $this->dispatcher->dispatch(
                    new ReindexationRequestEvent([Product::class], [], $productIds, true, ['main']),
                    ReindexationRequestEvent::EVENT_NAME
                );
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during triggering update of search index ',
                [
                    'exception' => $e,
                    'topic' => ReindexProductsByAttributesTopic::getName(),
                ]
            );

            return false;
        }
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Bundle\WebCatalogBundle\Resolver\DefaultVariantScopesResolver;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Schedule content node slug generation
 */
class ContentNodeSlugsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DefaultVariantScopesResolver
     */
    protected $defaultVariantScopesResolver;

    /**
     * @var SlugGenerator
     */
    protected $slugGenerator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var ResolveNodeSlugsMessageFactory
     */
    protected $messageFactory;

    /**
     * @var ContentNodeTreeCache
     */
    protected $contentNodeTreeCache;

    public function __construct(
        ManagerRegistry $registry,
        DefaultVariantScopesResolver $defaultVariantScopesResolver,
        SlugGenerator $slugGenerator,
        MessageProducerInterface $messageProducer,
        ResolveNodeSlugsMessageFactory $messageFactory,
        LoggerInterface $logger,
        ContentNodeTreeCache $contentNodeTreeCache
    ) {
        $this->registry = $registry;
        $this->defaultVariantScopesResolver = $defaultVariantScopesResolver;
        $this->slugGenerator = $slugGenerator;
        $this->messageProducer = $messageProducer;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
        $this->contentNodeTreeCache = $contentNodeTreeCache;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(ContentNode::class);
        $em->beginTransaction();

        try {
            $body = JSON::decode($message->getBody());
            $contentNode = $this->messageFactory->getEntityFromMessage($body);
            if (!$contentNode) {
                throw new InvalidArgumentException('Content Node not found');
            }
            $createRedirect = $this->messageFactory->getCreateRedirectFromMessage($body);

            $this->defaultVariantScopesResolver->resolve($contentNode);
            $this->slugGenerator->generate($contentNode, $createRedirect);

            $em->flush();
            $em->commit();

            /**
             * We need to clear content node cache here because of the next reasons:
             * 1) We need to clear cache for nodes which is not a part of the navigation catalog. It is necessary
             * to do because in the \Oro\Bundle\WebCatalogBundle\Async\WebCatalogCacheProcessor
             * only navigation catalog cache will be warmed up, so other nodes cache will not be cleared anywhere and
             * their cache state will be inconsistent with the DB state
             * 2) We need to clear cache for nodes which is a part of the navigation catalog. Because we could not
             * predict how fast async messages will be processed and all that time the cache for this node
             * will be inconsistent with the DB state
             *
             * @see \Oro\Bundle\WebCatalogBundle\Async\WebCatalogCacheProcessor::getRootNodesByWebCatalog
             *
             * Attention:
             * Correct cache regeneration will be available only after slugs recalculation
             * so this consequence of actions is important and should be preserved
             */
            $this->contentNodeTreeCache->deleteForNode($contentNode);

            $this->messageProducer->send(Topics::CALCULATE_WEB_CATALOG_CACHE, [
                'webCatalogId' => $contentNode->getWebCatalog()->getId()
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $em->rollback();

            return self::REQUEUE;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during content variant slugs processing',
                [
                    'topic' => Topics::RESOLVE_NODE_SLUGS,
                    'exception' => $e
                ]
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
        return [Topics::RESOLVE_NODE_SLUGS];
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
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
     * @param ManagerRegistry $registry
     * @param DefaultVariantScopesResolver $defaultVariantScopesResolver
     * @param SlugGenerator $slugGenerator
     * @param MessageProducerInterface $messageProducer
     * @param ResolveNodeSlugsMessageFactory $messageFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ManagerRegistry $registry,
        DefaultVariantScopesResolver $defaultVariantScopesResolver,
        SlugGenerator $slugGenerator,
        MessageProducerInterface $messageProducer,
        ResolveNodeSlugsMessageFactory $messageFactory,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->defaultVariantScopesResolver = $defaultVariantScopesResolver;
        $this->slugGenerator = $slugGenerator;
        $this->messageProducer = $messageProducer;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
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

            $createRedirect = $this->messageFactory->getCreateRedirectFromMessage($body);

            $this->defaultVariantScopesResolver->resolve($contentNode);
            $this->slugGenerator->generate($contentNode, $createRedirect);

            $em->flush();
            $em->commit();

            $this->messageProducer->send(Topics::CALCULATE_WEB_CATALOG_CACHE, $contentNode->getWebCatalog()->getId());
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during content variant slugs processing',
                [
                    'message' => $message->getBody(),
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

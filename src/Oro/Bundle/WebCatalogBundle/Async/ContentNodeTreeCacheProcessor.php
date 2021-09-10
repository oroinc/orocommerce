<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheDumper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dumps content node cache
 * Resets cache for cached node items
 */
class ContentNodeTreeCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const JOB_ID = 'jobId';
    const CONTENT_NODE = 'contentNode';
    const SCOPE = 'scope';

    /**
     * @var ContentNodeTreeCacheDumper
     */
    private $dumper;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var OptionsResolver
     */
    private $resolver;

    /**
     * @var CacheProvider
     */
    private $layoutCacheProvider;

    public function __construct(
        JobRunner $jobRunner,
        ContentNodeTreeCacheDumper $dumper,
        ManagerRegistry $registry,
        LoggerInterface $logger,
        CacheProvider $layoutCacheProvider
    ) {
        $this->jobRunner = $jobRunner;
        $this->dumper = $dumper;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->layoutCacheProvider = $layoutCacheProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $data = $this->getOptionsResolver()->resolve(JSON::decode($message->getBody()));
            $result = $this->jobRunner->runDelayed($data[self::JOB_ID], function () use ($data) {
                $this->dumper->dump($data[self::CONTENT_NODE], $data[self::SCOPE]);
                //Remove all cached layout data provider web catalog node items
                $this->layoutCacheProvider->deleteAll();

                return true;
            });

            return $result ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'topic' => Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                    'exception' => $e
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
        return [Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE];
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionsResolver()
    {
        if (!$this->resolver) {
            $this->resolver = new OptionsResolver();
            $this->resolver->setRequired([self::SCOPE, self::JOB_ID, self::CONTENT_NODE]);

            $this->resolver->setAllowedTypes(self::SCOPE, 'int');
            $this->resolver->setAllowedTypes(self::JOB_ID, 'int');
            $this->resolver->setAllowedTypes(self::CONTENT_NODE, 'int');

            $entityOptionNormalizer = function ($className) {
                return function (Options $options, $id) use ($className) {
                    $entity = $this->registry
                        ->getManagerForClass($className)
                        ->find($className, $id);

                    if (!$entity) {
                        throw new InvalidOptionsException(
                            sprintf('Could not find entity %s by given id "%s"', $className, $id)
                        );
                    }

                    return $entity;
                };
            };
            $this->resolver->setNormalizer(self::SCOPE, $entityOptionNormalizer(Scope::class));
            $this->resolver->setNormalizer(self::CONTENT_NODE, $entityOptionNormalizer(ContentNode::class));
        }

        return $this->resolver;
    }
}

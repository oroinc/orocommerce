<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SEOBundle\Model\SitemapIndexMessageFactory;
use Oro\Bundle\SEOBundle\Model\SitemapMessageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistry;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Website\WebsiteInterface;
use Psr\Log\LoggerInterface;

class GenerateSitemapProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var DependentJobService
     */
    private $dependentJobService;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var UrlItemsProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var WebsiteProviderInterface
     */
    private $websiteProvider;

    /**
     * @var SitemapIndexMessageFactory
     */
    private $indexMessageFactory;

    /**
     * @var SitemapMessageFactory
     */
    private $messageFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @var int
     */
    private $version;

    /**
     * @param JobRunner $jobRunner
     * @param DependentJobService $dependentJobService
     * @param MessageProducerInterface $producer
     * @param UrlItemsProviderRegistry $providerRegistry
     * @param WebsiteProviderInterface $websiteProvider
     * @param SitemapIndexMessageFactory $indexMessageFactory
     * @param SitemapMessageFactory $messageFactory
     * @param LoggerInterface $logger
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     */
    public function __construct(
        JobRunner $jobRunner,
        DependentJobService $dependentJobService,
        MessageProducerInterface $producer,
        UrlItemsProviderRegistry $providerRegistry,
        WebsiteProviderInterface $websiteProvider,
        SitemapIndexMessageFactory $indexMessageFactory,
        SitemapMessageFactory $messageFactory,
        LoggerInterface $logger,
        CanonicalUrlGenerator $canonicalUrlGenerator
    ) {
        $this->jobRunner = $jobRunner;
        $this->dependentJobService = $dependentJobService;
        $this->producer = $producer;
        $this->providerRegistry = $providerRegistry;
        $this->websiteProvider = $websiteProvider;
        $this->indexMessageFactory = $indexMessageFactory;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $this->version = time();

        try {
            $websites = $this->websiteProvider->getWebsites();
            $providerNames = $this->providerRegistry->getProviderNames();

            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::GENERATE_SITEMAP,
                function (JobRunner $jobRunner, Job $job) use ($providerNames, $websites) {
                    $context = $this->dependentJobService->createDependentJobContext($job->getRootJob());
                    foreach ($websites as $website) {
                        $this->canonicalUrlGenerator->clearCache($website);
                        foreach ($providerNames as $type) {
                            $this->scheduleGeneratingSitemapForWebsiteAndType($jobRunner, $website, $type);
                        }

                        $context->addDependentJob(
                            Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE,
                            $this->indexMessageFactory->createMessage($website, $this->version)
                        );
                    }
                    $this->dependentJobService->saveDependentJob($context);

                    return true;
                }
            );
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'Queue Message is invalid',
                [
                    'exception' => $e,
                    'message' => $message->getBody(),
                ]
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $e,
                    'message' => $message->getBody(),
                    'topic' => Topics::GENERATE_SITEMAP,
                ]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param JobRunner $jobRunner
     * @param WebsiteInterface $website
     * @param string $type
     */
    protected function scheduleGeneratingSitemapForWebsiteAndType(
        JobRunner $jobRunner,
        WebsiteInterface $website,
        $type
    ) {
        $jobRunner->createDelayed(
            sprintf(
                '%s:%s:%s',
                Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE,
                $website->getId(),
                $type
            ),
            function (JobRunner $jobRunner, Job $child) use ($website, $type) {
                $this->producer->send(
                    Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE,
                    $this->messageFactory->createMessage($website, $type, $this->version, $child)
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP];
    }
}

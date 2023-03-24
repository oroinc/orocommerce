<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapIndexTopic;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapTopic;
use Oro\Bundle\SEOBundle\Provider\WebsiteForSitemapProviderInterface;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Website\WebsiteUrlProvidersServiceInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Generates sitemaps for all websites.
 */
class GenerateSitemapProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private JobRunner $jobRunner;

    private DependentJobService $dependentJob;

    private MessageProducerInterface $producer;

    private WebsiteForSitemapProviderInterface $websiteProvider;

    private WebsiteUrlProvidersServiceInterface $websiteUrlProvidersService;

    private PublicSitemapFilesystemAdapter $fileSystemAdapter;

    private CanonicalUrlGenerator $canonicalUrlGenerator;

    private LoggerInterface $logger;

    public function __construct(
        JobRunner $jobRunner,
        DependentJobService $dependentJob,
        MessageProducerInterface $producer,
        WebsiteUrlProvidersServiceInterface $websiteUrlProvidersService,
        WebsiteForSitemapProviderInterface $websiteProvider,
        PublicSitemapFilesystemAdapter $fileSystemAdapter,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->dependentJob = $dependentJob;
        $this->producer = $producer;
        $this->websiteUrlProvidersService = $websiteUrlProvidersService;
        $this->websiteProvider = $websiteProvider;
        $this->fileSystemAdapter = $fileSystemAdapter;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [GenerateSitemapTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $version = time();
        try {
            // make sure that the temporary storage is empty before the sitemap dumping to it
            $this->fileSystemAdapter->clearTempStorage();

            $websites = $this->websiteProvider->getAvailableWebsites();
            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function (JobRunner $jobRunner, Job $job) use ($version, $websites) {
                    $this->createFinishJob($job, $version, $websites);
                    $this->scheduleGeneratingSitemap($jobRunner, $version, $websites);

                    return true;
                }
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during generating a sitemap.',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param Job $job
     * @param int $version
     * @param Website[] $websites
     */
    private function createFinishJob(Job $job, int $version, array $websites): void
    {
        $websiteIds = array_map(static fn (Website $website) => $website->getId(), $websites);

        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            GenerateSitemapIndexTopic::getName(),
            [
                GenerateSitemapIndexTopic::JOB_ID => $job->getId(),
                GenerateSitemapIndexTopic::VERSION => $version,
                GenerateSitemapIndexTopic::WEBSITE_IDS => $websiteIds,
            ]
        );
        $this->dependentJob->saveDependentJob($context);
    }

    /**
     * @param Website $website
     *
     * @return string[]
     */
    private function getProvidersNamesByWebsite(Website $website): array
    {
        return array_keys($this->websiteUrlProvidersService->getWebsiteProvidersIndexedByNames($website));
    }

    /**
     * @param JobRunner $jobRunner
     * @param int $version
     * @param Website[] $websites
     */
    private function scheduleGeneratingSitemap(JobRunner $jobRunner, int $version, array $websites): void
    {
        foreach ($websites as $website) {
            $this->canonicalUrlGenerator->clearCache($website);
            $providerNames = $this->getProvidersNamesByWebsite($website);
            foreach ($providerNames as $providerName) {
                $this->scheduleGeneratingSitemapForWebsiteAndType($jobRunner, $version, $website, $providerName);
            }
        }
    }

    private function scheduleGeneratingSitemapForWebsiteAndType(
        JobRunner $jobRunner,
        int $version,
        Website $website,
        string $type
    ): void {
        $jobRunner->createDelayed(
            sprintf('%s:%s:%s', GenerateSitemapByWebsiteAndTypeTopic::getName(), $website->getId(), $type),
            function (JobRunner $jobRunner, Job $child) use ($version, $website, $type) {
                $this->producer->send(
                    GenerateSitemapByWebsiteAndTypeTopic::getName(),
                    [
                        GenerateSitemapByWebsiteAndTypeTopic::JOB_ID => $child->getId(),
                        GenerateSitemapByWebsiteAndTypeTopic::VERSION => $version,
                        GenerateSitemapByWebsiteAndTypeTopic::WEBSITE_ID => $website->getId(),
                        GenerateSitemapByWebsiteAndTypeTopic::TYPE => $type,
                    ]
                );
            }
        );
    }
}

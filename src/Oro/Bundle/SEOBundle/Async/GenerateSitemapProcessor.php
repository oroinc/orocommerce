<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
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
    /** @var JobRunner */
    private $jobRunner;

    /** @var DependentJobService */
    private $dependentJob;

    /** @var MessageProducerInterface */
    private $producer;

    /** @var WebsiteForSitemapProviderInterface */
    private $websiteProvider;

    /** @var WebsiteUrlProvidersServiceInterface */
    private $websiteUrlProvidersService;

    /** @var PublicSitemapFilesystemAdapter */
    private $fileSystemAdapter;

    /** @var CanonicalUrlGenerator */
    private $canonicalUrlGenerator;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $useSingleThreadIndexGeneration = true;

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
     * @param bool $useSingleThreadIndexGeneration
     */
    public function setUseSingleThreadIndexGeneration(bool $useSingleThreadIndexGeneration): void
    {
        $this->useSingleThreadIndexGeneration = $useSingleThreadIndexGeneration;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP];
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
            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::GENERATE_SITEMAP,
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
     * @param Job       $job
     * @param int       $version
     * @param Website[] $websites
     */
    private function createFinishJob(Job $job, int $version, array $websites): void
    {
        $websiteIds = array_map(
            function (Website $website) {
                return $website->getId();
            },
            $websites
        );

        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            $this->useSingleThreadIndexGeneration ? Topics::GENERATE_SITEMAP_INDEX_ST : Topics::GENERATE_SITEMAP_INDEX,
            ['jobId' => $job->getId(), 'version' => $version, 'websiteIds' => $websiteIds]
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
     * @param int       $version
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
            sprintf('%s:%s:%s', Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE, $website->getId(), $type),
            function (JobRunner $jobRunner, Job $child) use ($version, $website, $type) {
                $this->producer->send(
                    Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE,
                    [
                        'jobId'     => $child->getId(),
                        'version'   => $version,
                        'websiteId' => $website->getId(),
                        'type'      => $type,
                    ]
                );
            }
        );
    }
}

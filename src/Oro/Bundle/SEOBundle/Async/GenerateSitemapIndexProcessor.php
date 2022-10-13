<?php

namespace Oro\Bundle\SEOBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapIndexTopic;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;

/**
 * Generates sitemap indexes for all websites and write it to a temporary storage.
 */
class GenerateSitemapIndexProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private SitemapDumperInterface $sitemapDumper,
        private PublicSitemapFilesystemAdapter $fileSystemAdapter,
        private LoggerInterface $logger,
        private WebsiteManager $websiteManager,
        private ?ConfigManager $configManager
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [GenerateSitemapIndexTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();

        $version = $messageBody[GenerateSitemapIndexTopic::VERSION];
        $websiteIds = $messageBody[GenerateSitemapIndexTopic::WEBSITE_IDS];

        $processedWebsiteIds = $this->generateSitemapIndexFiles($websiteIds, $version);
        if (!$processedWebsiteIds || !$this->moveSitemaps($processedWebsiteIds)) {
            return self::REJECT;
        }

        return self::ACK;
    }

    private function getWebsite(int $websiteId): ?Website
    {
        return $this->doctrine->getManagerForClass(Website::class)->find(Website::class, $websiteId);
    }

    private function generateSitemapIndexFiles(array $websiteIds, int $version): array
    {
        $processedWebsiteIds = [];
        foreach ($websiteIds as $websiteId) {
            $website = $this->getWebsite($websiteId);
            if (null === $website) {
                $this->logger->warning(
                    sprintf('The website with %d was not found during generating a sitemap index', $websiteId)
                );

                continue;
            }

            try {
                $this->websiteManager->setCurrentWebsite($website);
                $this->configManager?->setScopeId($website->getId());
                $this->sitemapDumper->dump($website, $version, 'index');
                $processedWebsiteIds[] = $websiteId;
            } catch (\Exception $e) {
                $this->logger->error(
                    'Unexpected exception occurred during generating a sitemap index for a website.',
                    [
                        'websiteId' => $websiteId,
                        'exception' => $e,
                    ]
                );

                continue;
            }
        }

        return $processedWebsiteIds;
    }

    private function moveSitemaps(array $websiteIds): bool
    {
        try {
            $this->fileSystemAdapter->moveSitemaps($websiteIds);

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during moving the generated sitemaps.',
                [
                    GenerateSitemapIndexTopic::WEBSITE_IDS => $websiteIds,
                    'exception' => $e,
                ]
            );

            return false;
        }
    }
}

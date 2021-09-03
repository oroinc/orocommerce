<?php

namespace Oro\Bundle\SEOBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generates sitemap indexes for all websites and write it to a temporary storage.
 * Single thread implementation.
 */
class GenerateSitemapIndexSingleThreadProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const VERSION = 'version';
    private const WEBSITE_IDS = 'websiteIds';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var SitemapDumperInterface */
    private $sitemapDumper;

    /** @var PublicSitemapFilesystemAdapter */
    private $fileSystemAdapter;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        SitemapDumperInterface $sitemapDumper,
        PublicSitemapFilesystemAdapter $fileSystemAdapter,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->sitemapDumper = $sitemapDumper;
        $this->fileSystemAdapter = $fileSystemAdapter;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP_INDEX_ST];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $this->resolveMessage($message);
        if (null === $body) {
            return self::REJECT;
        }

        $version = $body[self::VERSION];
        $websiteIds = $body[self::WEBSITE_IDS];

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

    private function resolveMessage(MessageInterface $message): ?array
    {
        try {
            return $this->getMessageResolver()->resolve(JSON::decode($message->getBody()));
        } catch (\Throwable $e) {
            $this->logger->critical('Got invalid message.', ['exception' => $e]);
        }

        return null;
    }

    private function getMessageResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined('jobId');
        $resolver->setRequired([self::VERSION, self::WEBSITE_IDS]);
        $resolver->setAllowedTypes(self::VERSION, ['int']);
        $resolver->setAllowedTypes(self::WEBSITE_IDS, ['array']);

        return $resolver;
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
                $this->sitemapDumper->dump($website, $version, 'index');
                $processedWebsiteIds[] = $websiteId;
            } catch (\Exception $e) {
                $this->logger->error(
                    'Unexpected exception occurred during generating a sitemap index for a website.',
                    [
                        'websiteId' => $websiteId,
                        'exception' => $e
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
                    'websiteIds' => $websiteIds,
                    'exception' => $e
                ]
            );

            return false;
        }
    }
}

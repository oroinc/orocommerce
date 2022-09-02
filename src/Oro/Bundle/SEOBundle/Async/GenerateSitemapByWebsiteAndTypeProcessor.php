<?php

namespace Oro\Bundle\SEOBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;

/**
 * Generates a sitemap of a specific type for a website and write it to a temporary storage.
 */
class GenerateSitemapByWebsiteAndTypeProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private JobRunner $jobRunner;

    private ManagerRegistry $doctrine;

    private SitemapDumperInterface $sitemapDumper;

    private LoggerInterface $logger;

    public function __construct(
        JobRunner $jobRunner,
        ManagerRegistry $doctrine,
        UrlItemsProviderRegistryInterface $urlItemsProviderRegistry,
        SitemapDumperInterface $sitemapDumper,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->doctrine = $doctrine;
        $this->sitemapDumper = $sitemapDumper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [GenerateSitemapByWebsiteAndTypeTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();

        try {
            $result = $this->jobRunner->runDelayed(
                $messageBody[GenerateSitemapByWebsiteAndTypeTopic::JOB_ID],
                function () use ($messageBody) {
                    $website = $this->getWebsite($messageBody[GenerateSitemapByWebsiteAndTypeTopic::WEBSITE_ID]);
                    if (null === $website) {
                        throw new \RuntimeException('The website does not exist.');
                    }
                    $this->sitemapDumper->dump(
                        $website,
                        $messageBody[GenerateSitemapByWebsiteAndTypeTopic::VERSION],
                        $messageBody[GenerateSitemapByWebsiteAndTypeTopic::TYPE]
                    );

                    return true;
                }
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during generating a sitemap of a specific type for a website.',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    private function getWebsite(int $websiteId): ?Website
    {
        return $this->doctrine->getManagerForClass(Website::class)->find(Website::class, $websiteId);
    }
}

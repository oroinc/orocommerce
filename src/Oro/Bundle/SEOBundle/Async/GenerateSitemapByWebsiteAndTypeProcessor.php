<?php

namespace Oro\Bundle\SEOBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
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
    public function __construct(
        private JobRunner $jobRunner,
        private ManagerRegistry $doctrine,
        private SitemapDumperInterface $sitemapDumper,
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

                    $this->websiteManager->setCurrentWebsite($website);
                    $this->configManager?->setScopeId($website->getId());

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
        return $this->doctrine->getManagerForClass(Website::class)?->find(Website::class, $websiteId);
    }
}

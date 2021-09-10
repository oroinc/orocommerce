<?php

namespace Oro\Bundle\SEOBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generates a sitemap of a specific type for a website and write it to a temporary storage.
 */
class GenerateSitemapByWebsiteAndTypeProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const JOB_ID     = 'jobId';
    private const VERSION    = 'version';
    private const WEBSITE_ID = 'websiteId';
    private const TYPE       = 'type';

    /** @var JobRunner */
    private $jobRunner;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var UrlItemsProviderRegistryInterface */
    private $urlItemsProviderRegistry;

    /** @var SitemapDumperInterface */
    private $sitemapDumper;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        JobRunner $jobRunner,
        ManagerRegistry $doctrine,
        UrlItemsProviderRegistryInterface $urlItemsProviderRegistry,
        SitemapDumperInterface $sitemapDumper,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->doctrine = $doctrine;
        $this->urlItemsProviderRegistry = $urlItemsProviderRegistry;
        $this->sitemapDumper = $sitemapDumper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE];
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

        try {
            $result = $this->jobRunner->runDelayed($body[self::JOB_ID], function () use ($body) {
                $website = $this->getWebsite($body[self::WEBSITE_ID]);
                if (null === $website) {
                    throw new \RuntimeException('The website does not exist.');
                }
                $this->sitemapDumper->dump($website, $body[self::VERSION], $body[self::TYPE]);

                return true;
            });
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during generating a sitemap of a specific type for a website.',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
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
        $resolver->setRequired([self::JOB_ID, self::VERSION, self::WEBSITE_ID, self::TYPE]);
        $resolver->setAllowedTypes(self::JOB_ID, ['int']);
        $resolver->setAllowedTypes(self::VERSION, ['int']);
        $resolver->setAllowedTypes(self::WEBSITE_ID, ['int']);
        $resolver->setAllowedTypes(self::TYPE, ['string']);
        $resolver->setAllowedValues(
            self::TYPE,
            array_keys($this->urlItemsProviderRegistry->getProvidersIndexedByNames())
        );

        return $resolver;
    }

    private function getWebsite(int $websiteId): ?Website
    {
        return $this->doctrine->getManagerForClass(Website::class)->find(Website::class, $websiteId);
    }
}

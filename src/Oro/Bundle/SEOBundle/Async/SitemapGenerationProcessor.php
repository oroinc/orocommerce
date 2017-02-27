<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\SEOBundle\Provider\UrlItemsProviderRegistry;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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

class SitemapGenerationProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var WebsiteRepository
     */
    private $websiteRepository;

    /**
     * @var SitemapDumper
     */
    private $sitemapDumper;

    /**
     * @var UrlItemsProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     * @param SitemapDumper $sitemapDumper
     * @param WebsiteRepository $websiteRepository
     * @param UrlItemsProviderRegistry $providerRegistry
     */
    public function __construct(
        JobRunner $jobRunner,
        LoggerInterface $logger,
        SitemapDumper $sitemapDumper,
        WebsiteRepository $websiteRepository,
        UrlItemsProviderRegistry $providerRegistry
    ) {
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
        $this->sitemapDumper = $sitemapDumper;
        $this->websiteRepository = $websiteRepository;
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        try {
            $data = $this->resolveOptions($data);
        } catch (\Exception $exception) {
            $this->logger->error(
                '[SitemapGenerationProcessor] Got invalid message',
                [
                    'message' => $message->getBody(),
                    'exception' => $exception
                ]
            );

            return self::REJECT;
        }

        try {
            $result = $this->jobRunner->runDelayed($data['jobId'], function () use ($data, $message) {
                /** @var Website $website */
                $website = $this->websiteRepository->find($data['websiteId']);

                $this->sitemapDumper->dump($website, $data['type']);

                return true;
            });
        } catch (\Exception $exception) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'message' => $message->getBody(),
                    'exception' => $exception,
                    'topic' => Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE
                ]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param array $options
     * @return array
     * @throws InvalidOptionsException
     */
    private function resolveOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();

        $optionsResolver->setRequired(['websiteId', 'type', 'jobId']);

        $optionsResolver->setAllowedTypes('jobId', 'int');
        $optionsResolver->setAllowedTypes('websiteId', 'int');
        $optionsResolver->setAllowedTypes('type', 'string');

        $optionsResolver->setNormalizer('websiteId', function (Options $options, $websiteId) {
            if (!$this->websiteRepository->checkWebsiteExists($websiteId)) {
                throw new InvalidOptionsException(sprintf('No website exists with id "%d"', $websiteId));
            }

            return $websiteId;
        });

        $optionsResolver->setNormalizer('type', function (Options $options, $type) {
            if (!$this->providerRegistry->hasProviderByName($type)) {
                throw new InvalidOptionsException(sprintf('No url item provider exists with name "%s"', $type));
            }

            return $type;
        });

        return $optionsResolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE];
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Calculate web catalog cache on cache warm up
 */
class ContentNodeTreeCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $repository = $this->doctrineHelper->getEntityRepository(Website::class);
        $websites = $repository->findAll();
        $webCatalogValues = $this->configManager->getValues('oro_web_catalog.web_catalog', $websites);

        foreach (array_unique($webCatalogValues) as $value) {
            if ($value) {
                $this->messageProducer->send(Topics::CALCULATE_WEB_CATALOG_CACHE, ['webCatalogId' => $value]);
            }
        }
    }
}

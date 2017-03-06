<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\DependencyInjection\Configuration as SeoConfiguration;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UrlItemsProvider implements UrlItemsProviderInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Provider type, like 'product' for example.
     *
     * @var string
     */
    private $type;

    /**
     * Fully qualified name of the supported by provider entity class.
     *
     * @var string
     */
    private $entityClass;

    /**
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param ConfigManager $configManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param $type
     * @param $entityClass
     */
    public function __construct(
        CanonicalUrlGenerator $canonicalUrlGenerator,
        ConfigManager $configManager,
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        $type,
        $entityClass
    ) {
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->configManager = $configManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $doctrineHelper->getEntityManagerForClass($entityClass);
        $this->type = $type;
        $this->entityClass = $entityClass;
    }

    /**
     * @param WebsiteInterface $website
     * @return \Generator|UrlItem[]
     */
    public function getUrlItems(WebsiteInterface $website)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select(str_replace('entityAlias', $this->type, 'entityAlias.id, entityAlias.updatedAt'))
            ->from($this->entityClass, $this->type);

        $canonicalUrlType = $this->canonicalUrlGenerator->getCanonicalUrlType();
        if ($canonicalUrlType === Configuration::DIRECT_URL) {
            $queryBuilder->addSelect('slugs.url');
            $queryBuilder->leftJoin(sprintf('%s.slugs', $this->type), 'slugs');
        }

        $event = new RestrictSitemapEntitiesEvent($queryBuilder, $website);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', RestrictSitemapEntitiesEvent::NAME, $this->type),
            $event
        );

        $entityChangeFrequency = $this->getEntityChangeFrequency($website);
        $entitySitemapPriority = $this->getEntitySitemapPriority($website);
        $iterableResult = new BufferedQueryResultIterator($queryBuilder);
        foreach ($iterableResult as $row) {
            $entityUrlItem = $this->getEntityUrlItem(
                $website,
                $row,
                $canonicalUrlType,
                $entityChangeFrequency,
                $entitySitemapPriority
            );

            if ($entityUrlItem) {
                yield $entityUrlItem;
            }
        }
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return string
     */
    private function getEntityChangeFrequency(WebsiteInterface $website)
    {
        return $this->configManager->get(
            sprintf('oro_seo.sitemap_changefreq_%s', $this->type),
            SeoConfiguration::CHANGEFREQ_DAILY,
            false,
            $website
        );
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return string
     */
    private function getEntitySitemapPriority(WebsiteInterface $website)
    {
        return $this->configManager->get(
            sprintf('oro_seo.sitemap_priority_%s', $this->type),
            SeoConfiguration::DEFAULT_PRIORITY,
            false,
            $website
        );
    }

    /**
     * @param WebsiteInterface $website
     * @param array $row
     * @param string $canonicalUrlType
     * @param string $entityChangeFrequency
     * @param string $entitySitemapPriority
     *
     * @return null|UrlItem
     */
    private function getEntityUrlItem(
        WebsiteInterface $website,
        array $row,
        $canonicalUrlType,
        $entityChangeFrequency,
        $entitySitemapPriority
    ) {
        $entityReference = $this->entityManager->getPartialReference($this->entityClass, $row['id']);
        $updatedAt = $row['updatedAt'];
        $url = isset($row['url']) ? $row['url']: '';

        $this->entityManager->detach($entityReference);

        if ($canonicalUrlType === Configuration::DIRECT_URL) {
            $entityUrlItem = $this->getDirectUrlItem(
                $website,
                $updatedAt,
                $url,
                $entityChangeFrequency,
                $entitySitemapPriority
            );
        } else {
            $entityUrlItem = $this->getSystemUrlItem(
                $website,
                $updatedAt,
                $entityReference,
                $entityChangeFrequency,
                $entitySitemapPriority
            );
        }

        return $entityUrlItem;
    }

    /**
     * @param WebsiteInterface $website
     * @param \Datetime $updatedAt
     * @param string $url
     * @param string $entityChangeFrequency
     * @param string $entitySitemapPriority
     *
     * @return null|UrlItem
     */
    private function getDirectUrlItem(
        WebsiteInterface $website,
        \DateTime $updatedAt,
        $url,
        $entityChangeFrequency,
        $entitySitemapPriority
    ) {
        if ($url) {
            $absoluteUrl = $this->canonicalUrlGenerator->getAbsoluteUrl($url, $website);

            if ($absoluteUrl) {
                $urlItem = new UrlItem(
                    $absoluteUrl,
                    $updatedAt,
                    $entityChangeFrequency,
                    $entitySitemapPriority
                );

                return $urlItem;
            }
        }

        return null;
    }

    /**
     * @param WebsiteInterface $website
     * @param \DateTime $updatedAt
     * @param SluggableInterface $entityReference
     * @param string $entityChangeFrequency
     * @param string $entitySitemapPriority
     *
     * @return null|UrlItem
     */
    private function getSystemUrlItem(
        WebsiteInterface $website,
        \DateTime $updatedAt,
        SluggableInterface $entityReference,
        $entityChangeFrequency,
        $entitySitemapPriority
    ) {
        $systemUrl = $this->canonicalUrlGenerator->getSystemUrl($entityReference, $website);

        if ($systemUrl) {
            return new UrlItem(
                $systemUrl,
                $updatedAt,
                $entityChangeFrequency,
                $entitySitemapPriority
            );
        }

        return null;
    }
}

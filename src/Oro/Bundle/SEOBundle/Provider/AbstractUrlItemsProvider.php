<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\SEOBundle\DependencyInjection\Configuration as SeoConfiguration;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractUrlItemsProvider implements UrlItemsProviderInterface
{
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
     * Should return provider type, like 'product' for example.
     *
     * @return string
     */
    abstract protected function getType();

    /**
     * Should return fully qualified name of the supported class.
     *
     * @return string
     */
    abstract protected function getEntityClass();

    /**
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param ConfigManager $configManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        CanonicalUrlGenerator $canonicalUrlGenerator,
        ConfigManager $configManager,
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper
    ) {
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->configManager = $configManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $doctrineHelper->getEntityManagerForClass($this->getEntityClass());
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select(str_replace('entityAlias', $this->getType(), 'entityAlias.id, entityAlias.updatedAt'))
            ->from($this->getEntityClass(), $this->getType());

        $canonicalUrlType = $this->configManager->get('oro_redirect.canonical_url_type');
        if ($canonicalUrlType === Configuration::DIRECT_URL) {
            $queryBuilder->addSelect('slugs.url');
            $queryBuilder->leftJoin(sprintf('%s.slugs', $this->getType()), 'slugs');
        }

        $event = new RestrictSitemapEntitiesEvent($queryBuilder, $website);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', RestrictSitemapEntitiesEvent::NAME, $this->getType()),
            $event
        );

        $iterableResult = new BufferedQueryResultIterator($queryBuilder);
        foreach ($iterableResult as $row) {
            $entityUrlItem = $this->getEntityUrlItem($website, $row, $canonicalUrlType);

            if ($entityUrlItem) {
                yield $entityUrlItem;
            }
        }
    }

    /**
     * @return string
     */
    private function getEntityChangeFrequency()
    {
        return $this->configManager->get(
            sprintf('oro_seo.sitemap_changefreq_%s', $this->getType()),
            SeoConfiguration::CHANGEFREQ_DAILY
        );
    }

    /**
     * @return string
     */
    private function getEntitySitemapPriority()
    {
        return $this->configManager->get(
            sprintf('oro_seo.sitemap_priority_%s', $this->getType()),
            SeoConfiguration::DEFAULT_PRIORITY
        );
    }

    /**
     * @param WebsiteInterface $website
     * @param array $row
     * @param string $canonicalUrlType
     * @return UrlItem|null
     */
    private function getEntityUrlItem(WebsiteInterface $website, array $row, $canonicalUrlType)
    {
        $entityReference = $this->entityManager->getReference($this->getEntityClass(), $row['id']);
        $updatedAt = $row['updatedAt'];
        $url = isset($row['url']) ? $row['url']: '';

        $this->entityManager->detach($entityReference);

        if ($canonicalUrlType === Configuration::DIRECT_URL) {
            $entityUrlItem = $this->getDirectUrlItem($website, $updatedAt, $url);
        } else {
            $entityUrlItem = $this->getSystemUrlItem($website, $updatedAt, $entityReference);
        }

        return $entityUrlItem;
    }

    /**
     * @param WebsiteInterface $website
     * @param \Datetime $updatedAt
     * @param string $url
     * @return UrlItem|null
     */
    private function getDirectUrlItem(WebsiteInterface $website, \DateTime $updatedAt, $url)
    {
        if ($url) {
            $absoluteUrl = $this->canonicalUrlGenerator->getAbsoluteUrl($url, $website);

            if ($absoluteUrl) {
                $urlItem = new UrlItem(
                    $absoluteUrl,
                    $this->getEntityChangeFrequency(),
                    $this->getEntitySitemapPriority(),
                    $updatedAt
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
     * @return UrlItem|null
     */
    private function getSystemUrlItem(
        WebsiteInterface $website,
        \DateTime $updatedAt,
        SluggableInterface $entityReference
    ) {
        $systemUrl = $this->canonicalUrlGenerator->getSystemUrl($entityReference, $website);

        if ($systemUrl) {
            return new UrlItem(
                $systemUrl,
                $updatedAt,
                $this->getEntityChangeFrequency(),
                $this->getEntitySitemapPriority()
            );
        }

        return null;
    }
}

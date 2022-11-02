<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryWithDoctrineIterableResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Event\UrlItemsProviderEvent;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides UrlItems for sitemap generation
 */
class UrlItemsProvider implements UrlItemsProviderInterface
{
    const ENTITY_ALIAS = 'entityAlias';
    const BUFFER_SIZE = 50000;

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
     * @var ManagerRegistry
     */
    private $registry;

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
     * @var string|null
     */
    private $changeFrequencySettingsKey;

    /**
     * @var string|null
     */
    private $prioritySettingsKey;

    /**
     * @var bool
     */
    private $isDirectUrlEnabled = false;

    /**
     * @var string|null
     */
    private $entityChangeFrequency;

    /**
     * @var string|null
     */
    private $entitySitemapPriority;

    public function __construct(
        CanonicalUrlGenerator $canonicalUrlGenerator,
        ConfigManager $configManager,
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $registry
    ) {
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->configManager = $configManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->registry = $registry;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @param string $changeFrequencySettingsKey
     */
    public function setChangeFrequencySettingsKey($changeFrequencySettingsKey)
    {
        $this->changeFrequencySettingsKey = $changeFrequencySettingsKey;
    }

    /**
     * @param string $prioritySettingsKey
     */
    public function setPrioritySettingsKey($prioritySettingsKey)
    {
        $this->prioritySettingsKey = $prioritySettingsKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        $this->loadConfigs($website);

        $this->dispatchIterationEvent(UrlItemsProviderEvent::ON_START, $website, $version);

        foreach ($this->getResultIterator($website, $version) as $row) {
            $entityUrlItem = $this->getEntityUrlItem($website, $row);

            if ($entityUrlItem) {
                yield $entityUrlItem;
            }
        }

        $this->dispatchIterationEvent(UrlItemsProviderEvent::ON_END, $website, $version);
    }

    /**
     * @param WebsiteInterface $website
     * @param string $version
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder(WebsiteInterface $website, $version)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->from($this->entityClass, self::ENTITY_ALIAS)
            ->select($this->getFieldName('id'))
            ->distinct(true)
            ->orderBy($this->getFieldName('id'), 'DESC');

        if (is_a($this->entityClass, UpdatedAtAwareInterface::class, true)) {
            $queryBuilder->addSelect($this->getFieldName('updatedAt'));
        }

        if ($this->isDirectUrlEnabled && is_a($this->entityClass, SlugAwareInterface::class, true)) {
            $queryBuilder->leftJoin($this->getFieldName('slugs'), 'slugs');
            $queryBuilder->addSelect('slugs.url');
        }

        $this->dispatchQueryBuilderRestrictionEvent($queryBuilder, $website, $version);

        return $queryBuilder;
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    protected function getFieldName($fieldName)
    {
        return self::ENTITY_ALIAS . '.' . $fieldName;
    }

    /**
     * @param WebsiteInterface $website
     * @param string $version
     *
     * @return \Iterator
     */
    protected function getResultIterator(WebsiteInterface $website, $version)
    {
        $resultIterator = new BufferedQueryWithDoctrineIterableResultIterator(
            $this->getQueryBuilder($website, $version)
        );
        $resultIterator->setBufferSize(self::BUFFER_SIZE);
        $resultIterator->setReverse(true);

        return $resultIterator;
    }

    /**
     * @param WebsiteInterface $website
     * @param array $row
     *
     * @return null|UrlItem
     */
    private function getEntityUrlItem(WebsiteInterface $website, array $row)
    {
        $em = $this->getEntityManager();
        $entityReference = $em->getPartialReference($this->entityClass, $row['id']);
        $em->detach($entityReference);

        $updatedAt = isset($row['updatedAt']) ? $row['updatedAt'] : null;
        $url = isset($row['url']) ? $row['url'] : null;

        $entityUrlItem = null;
        if ($this->isDirectUrlEnabled) {
            $entityUrlItem = $this->getDirectUrlItem($website, $url, $updatedAt);
        }

        if (!$entityUrlItem) {
            $entityUrlItem = $this->getSystemUrlItem($website, $entityReference, $updatedAt);
        }

        return $entityUrlItem;
    }

    /**
     * @param WebsiteInterface $website
     * @param string|null $url
     * @param \DateTime|null $updatedAt
     * @return null|UrlItem
     */
    private function getDirectUrlItem(WebsiteInterface $website, $url, \DateTime $updatedAt = null)
    {
        if ($url) {
            $absoluteUrl = $this->canonicalUrlGenerator->getAbsoluteUrl($url, $website);

            if ($absoluteUrl) {
                $urlItem = new UrlItem(
                    $absoluteUrl,
                    $updatedAt,
                    $this->entityChangeFrequency,
                    $this->entitySitemapPriority
                );

                return $urlItem;
            }
        }

        return null;
    }

    /**
     * @param WebsiteInterface $website
     * @param SluggableInterface $entityReference
     * @param \DateTime|null $updatedAt
     * @return null|UrlItem
     */
    private function getSystemUrlItem(
        WebsiteInterface $website,
        SluggableInterface $entityReference,
        \DateTime $updatedAt = null
    ) {
        $systemUrl = $this->canonicalUrlGenerator->getSystemUrl($entityReference, $website);

        if ($systemUrl) {
            return $this->createUrlItem($systemUrl, $updatedAt);
        }

        return null;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass($this->entityClass);
    }

    /**
     * @param string $eventName
     * @param WebsiteInterface $website
     * @param string $version
     */
    private function dispatchIterationEvent($eventName, WebsiteInterface $website, $version)
    {
        $this->eventDispatcher->dispatch(
            new UrlItemsProviderEvent($version, $website),
            sprintf('%s.%s', $eventName, $this->type)
        );
        $this->eventDispatcher->dispatch(new UrlItemsProviderEvent($version, $website), $eventName);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param WebsiteInterface $website
     * @param string $version
     */
    private function dispatchQueryBuilderRestrictionEvent(
        QueryBuilder $queryBuilder,
        WebsiteInterface $website,
        $version
    ) {
        $this->eventDispatcher->dispatch(
            new RestrictSitemapEntitiesEvent($queryBuilder, $version, $website),
            sprintf('%s.%s', RestrictSitemapEntitiesEvent::NAME, $this->type)
        );
        $this->eventDispatcher->dispatch(
            new RestrictSitemapEntitiesEvent($queryBuilder, $version, $website),
            RestrictSitemapEntitiesEvent::NAME
        );
    }

    private function loadConfigs(WebsiteInterface $website)
    {
        $this->isDirectUrlEnabled = $this->canonicalUrlGenerator->isDirectUrlEnabled($website);
        $this->entityChangeFrequency = $this->getEntityChangeFrequency($website);
        $this->entitySitemapPriority = $this->getEntitySitemapPriority($website);
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return string|null
     */
    protected function getEntityChangeFrequency(WebsiteInterface $website)
    {
        if ($this->changeFrequencySettingsKey) {
            return $this->configManager->get($this->changeFrequencySettingsKey, false, false, $website);
        }

        return null;
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return string|null
     */
    protected function getEntitySitemapPriority(WebsiteInterface $website)
    {
        if ($this->prioritySettingsKey) {
            return $this->configManager->get($this->prioritySettingsKey, false, false, $website);
        }

        return null;
    }

    /**
     * @param string $url
     * @param \DateTime|null $updatedAt
     *
     * @return UrlItem
     */
    private function createUrlItem($url, \DateTime $updatedAt = null)
    {
        return new UrlItem(
            $url,
            $updatedAt,
            $this->entityChangeFrequency,
            $this->entitySitemapPriority
        );
    }
}

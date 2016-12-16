<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\ContentVariantProvider\ContentVariantProvider;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class WebCatalogEntityIndexerListener
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var WebsiteLocalizationProvider
     */
    private $websiteLocalizationProvider;

    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManager;

    /**
     * @var ContentVariantProvider
     */
    private $contentVariantProvider;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     * @param AbstractWebsiteLocalizationProvider $websiteLocalizationProvider
     * @param WebsiteContextManager $websiteContextManager
     * @param ContentVariantProvider $contentVariantProvider
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager,
        ContentVariantProvider $contentVariantProvider,
        LocalizationHelper $localizationHelper
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManager = $websiteContextManager;
        $this->contentVariantProvider = $contentVariantProvider;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();
            return;
        }

        if (!$this->contentVariantProvider->isSupportedClass($event->getEntityClass())) {
            return;
        }

        $website = $this->registry->getManagerForClass(Website::class)
            ->getRepository(Website::class)
            ->find($websiteId);
        $webCatalogId = $this->configManager->get('oro_web_catalog.web_catalog', false, false, $website);
        if (!$webCatalogId) {
            return;
        }

        $webCatalog = $this->registry->getManagerForClass(WebCatalog::class)
            ->getRepository(WebCatalog::class)
            ->find($webCatalogId);
        if (!$webCatalog) {
            return;
        }

        $relationQueryBuilder = $this->registry->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class)
            ->getContentVariantQueryBuilder($webCatalog);

        $this->contentVariantProvider->modifyNodeQueryBuilderByEntities(
            $relationQueryBuilder,
            $event->getEntityClass(),
            $event->getEntities()
        );

        $relations = $relationQueryBuilder->getQuery()->getArrayResult();

        $nodes = $this->getRelatedNodes($relations);
        if (!$nodes) {
            return;
        }

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);

        $this->addInformationToIndex($event, $localizations, $relations, $nodes);
    }

    /**
     * @param array $relations
     * @return ContentNode[]
     */
    protected function getRelatedNodes(array $relations)
    {
        $nodeIds = [];
        foreach ($relations as $relation) {
            if ($relation['nodeId'] && !in_array($relation['nodeId'], $nodeIds, true)) {
                $nodeIds[] = $relation['nodeId'];
            }
        }
        if (!$nodeIds) {
            return [];
        }

        $nodesQueryBuilder = $this->registry->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class)
            ->getNodesByIds($nodeIds);

        $nodes = [];
        /** @var ContentNode $node */
        foreach ($nodesQueryBuilder->getQuery()->getResult() as $node) {
            $nodes[$node->getId()] = $node;
        }

        return $nodes;
    }

    /**
     * @param IndexEntityEvent $event
     * @param Localization[] $localizations
     * @param array $relations
     * @param ContentNode[] $nodes
     */
    protected function addInformationToIndex(
        IndexEntityEvent $event,
        array $localizations,
        array $relations,
        array $nodes
    ) {
        foreach ($relations as $relation) {
            if (empty($relation['nodeId'])) {
                continue;
            }

            $nodeId = $relation['nodeId'];
            $node = $nodes[$nodeId];

            $recordId = $this->contentVariantProvider->getRecordId($relation);
            if (!$recordId) {
                continue;
            }

            $plainValues = $this->contentVariantProvider->getValues($node);
            $localizedValues = $this->contentVariantProvider->getLocalizedValues($node);

            foreach ($localizations as $localization) {
                $placeholders = [LocalizationIdPlaceholder::NAME => $localization->getId()];
                $event->addPlaceholderField(
                    $recordId,
                    IndexDataProvider::ALL_TEXT_L10N_FIELD,
                    (string)$this->localizationHelper->getLocalizedValue($node->getTitles(), $localization),
                    $placeholders,
                    true
                );

                foreach ($plainValues as $value) {
                    $event->addPlaceholderField(
                        $recordId,
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        (string)$value,
                        $placeholders,
                        true
                    );
                }

                foreach ($localizedValues as $value) {
                    $event->addPlaceholderField(
                        $recordId,
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        (string)$this->localizationHelper->getLocalizedValue($value, $localization),
                        $placeholders,
                        true
                    );
                }
            }
        }
    }
}

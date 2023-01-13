<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SearchBundle\Formatter\ValueFormatterInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignTypePlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;

/**
 * This class adds information about web catalog association to website search index
 */
class WebCatalogEntityIndexerListener
{
    use ContextTrait;

    const ASSIGN_TYPE_CONTENT_VARIANT = 'variant';

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
     * @var ContentVariantProviderInterface
     */
    private $contentVariantProvider;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var ValueFormatterInterface
     */
    private $decimalValueFormatter;

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager,
        ContentVariantProviderInterface $contentVariantProvider,
        LocalizationHelper $localizationHelper,
        ValueFormatterInterface $decimalValueFormatter
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManager = $websiteContextManager;
        $this->contentVariantProvider = $contentVariantProvider;
        $this->localizationHelper = $localizationHelper;
        $this->decimalValueFormatter = $decimalValueFormatter;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'main')
            && !$this->hasContextFieldGroup($event->getContext(), 'collection_sort_order')) {
            return;
        }

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

        $this->processInformationAndAddToIndex($event, $websiteId, $relations, $nodes);
    }

    /**
     * Rules which information to add to index depending on context (partial indexation group)
     *
     * @param IndexEntityEvent $event
     * @param int $websiteId
     * @param array $relations
     * @param array $nodes
     * @return void
     */
    protected function processInformationAndAddToIndex(
        IndexEntityEvent $event,
        int $websiteId,
        array $relations,
        array $nodes
    ): void {
        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);

        if ($this->hasContextFieldGroup($event->getContext(), 'main') &&
            $this->hasContextFieldGroup($event->getContext(), 'collection_sort_order')
        ) {
            $this->addInformationToIndex($event, $localizations, $relations, $nodes, true);
        } elseif ($this->hasContextFieldGroup($event->getContext(), 'main')) {
            $this->addInformationToIndex($event, $localizations, $relations, $nodes);
        } elseif ($this->hasContextFieldGroup($event->getContext(), 'collection_sort_order')) {
            $this->addCollectionSortOrderInformationToIndex($event, $relations);
        }
    }

    /**
     * @param array $relations
     * @return ContentNode[]
     */
    protected function getRelatedNodes(array $relations): array
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

        $nodes = $this->registry->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class)
            ->getNodesByIds($nodeIds);

        $indexedNodes = [];
        /** @var ContentNode $node */
        foreach ($nodes as $node) {
            $indexedNodes[$node->getId()] = $node;
        }

        return $indexedNodes;
    }

    /**
     * @param IndexEntityEvent $event
     * @param Localization[] $localizations
     * @param array $relations
     * @param ContentNode[] $nodes
     * @param bool $addSortOrder
     */
    protected function addInformationToIndex(
        IndexEntityEvent $event,
        array $localizations,
        array $relations,
        array $nodes,
        bool $addSortOrder = false
    ): void {
        foreach ($relations as $relation) {
            if (empty($relation['nodeId'])) {
                continue;
            }

            $nodeId = $relation['nodeId'];
            $variantId = $relation['variantId'];
            $node = $nodes[$nodeId];

            $recordId = $this->contentVariantProvider->getRecordId($relation);
            if (!$recordId) {
                continue;
            }

            $plainValues = $this->contentVariantProvider->getValues($node);
            $localizedValues = $this->contentVariantProvider->getLocalizedValues($node);

            $event->addPlaceholderField(
                $recordId,
                'assigned_to.ASSIGN_TYPE_ASSIGN_ID',
                1,
                [
                    AssignTypePlaceholder::NAME => self::ASSIGN_TYPE_CONTENT_VARIANT,
                    AssignIdPlaceholder::NAME => $variantId,
                ]
            );

            if ($addSortOrder && !is_null($relation['sortOrderValue'])) {
                $this->addCollectionSortOrderInformation($event, $relation);
            }

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

    /**
     * @param IndexEntityEvent $event
     * @param array $relations
     */
    protected function addCollectionSortOrderInformationToIndex(IndexEntityEvent $event, array $relations): void
    {
        foreach ($relations as $relation) {
            if (empty($relation['nodeId'])) {
                continue;
            }

            $this->addCollectionSortOrderInformation($event, $relation);
        }
    }

    /**
     * @param IndexEntityEvent $event
     * @param array $relation
     * @return void
     */
    protected function addCollectionSortOrderInformation(IndexEntityEvent $event, array $relation): void
    {
        $recordId = $this->contentVariantProvider->getRecordId($relation);
        if (!$recordId) {
            return;
        }

        $recordSortOrderValue = $this->contentVariantProvider->getRecordSortOrder($relation);
        if (is_null($recordSortOrderValue)) {
            return;
        }

        $event->addPlaceholderField(
            $recordId,
            'assigned_to_sort_order.ASSIGN_TYPE_ASSIGN_ID',
            $this->decimalValueFormatter->format($recordSortOrderValue),
            [
                AssignTypePlaceholder::NAME => self::ASSIGN_TYPE_CONTENT_VARIANT,
                AssignIdPlaceholder::NAME => $relation['variantId'],
            ]
        );
    }
}

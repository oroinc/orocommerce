<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogEntityIndexerListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignTypePlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;

/**
 * Search indexer listener that adds information for each record that was assigned to product collection manually.
 * This information can be used for custom queries by manually added products.
 */
class ManuallyAddedProductCollectionIndexerListener implements WebsiteSearchProductIndexerListenerInterface
{
    use ContextTrait;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManager;

    /**
     * @var ContentVariantProviderInterface
     */
    private $contentVariantProvider;

    /**
     * @var ProductCollectionDefinitionConverter
     */
    private $productCollectionDefinitionConverter;

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        WebsiteContextManager $websiteContextManager,
        ContentVariantProviderInterface $contentVariantProvider,
        ProductCollectionDefinitionConverter $productCollectionDefinitionConverter
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->websiteContextManager = $websiteContextManager;
        $this->contentVariantProvider = $contentVariantProvider;
        $this->productCollectionDefinitionConverter = $productCollectionDefinitionConverter;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'main')) {
            return;
        }

        if (!$this->isApplicable($event)) {
            return;
        }

        $variantsByRecordId = $this->collectVariantIdsByRecordId($event->getEntitiesData());
        if (empty($variantsByRecordId)) {
            return;
        }

        $manuallyAddedByVariantId = $this->collectManuallyAddedByVariantId($variantsByRecordId);
        if (empty($manuallyAddedByVariantId)) {
            return;
        }

        $this->filterManuallyAddedRecords($variantsByRecordId, $manuallyAddedByVariantId);
        foreach ($variantsByRecordId as $recordId => $variantIds) {
            foreach ($variantIds as $variantId) {
                $event->addPlaceholderField(
                    $recordId,
                    'manually_added_to.ASSIGN_TYPE_ASSIGN_ID',
                    1,
                    [
                        AssignTypePlaceholder::NAME => WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                        AssignIdPlaceholder::NAME => $variantId,
                    ]
                );
            }
        }
    }

    protected function isApplicable(IndexEntityEvent $event): bool
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();
            return false;
        }

        if (!$this->contentVariantProvider->isSupportedClass($event->getEntityClass())) {
            return false;
        }

        $website = $this->registry->getManagerForClass(Website::class)
            ->getRepository(Website::class)
            ->find($websiteId);
        $webCatalogId = $this->configManager->get('oro_web_catalog.web_catalog', false, false, $website);
        if (!$webCatalogId) {
            return false;
        }

        $webCatalog = $this->registry->getManagerForClass(WebCatalog::class)
            ->getRepository(WebCatalog::class)
            ->find($webCatalogId);
        if (!$webCatalog) {
            return false;
        }

        return true;
    }

    protected function collectVariantIdsByRecordId(array $entitiesData): array
    {
        $variantsByRecordId = [];
        foreach ($entitiesData as $recordId => $data) {
            if (empty($data['assigned_to.ASSIGN_TYPE_ASSIGN_ID'])
                || !\is_array($data['assigned_to.ASSIGN_TYPE_ASSIGN_ID'])
            ) {
                continue;
            }

            foreach ($data['assigned_to.ASSIGN_TYPE_ASSIGN_ID'] as $assignData) {
                /** @var PlaceholderValue $placeholderValue */
                $placeholderValue = $assignData['value'];
                $placeholders = $placeholderValue->getPlaceholders();
                if (isset($placeholders[AssignTypePlaceholder::NAME])
                    && $placeholders[AssignTypePlaceholder::NAME]
                    === WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT
                ) {
                    $variantsByRecordId[$recordId][] = $placeholders[AssignIdPlaceholder::NAME];
                }
            }
        }

        return $variantsByRecordId;
    }

    protected function collectManuallyAddedByVariantId(array $variantsByRecordId): array
    {
        $variantIds = [];
        if ($variantsByRecordId) {
            $variantIds = array_merge(...array_values($variantsByRecordId));
        }

        $repository = $this->registry->getManagerForClass(ContentVariant::class)->getRepository(ContentVariant::class);
        $builder = $repository->createQueryBuilder('cv');
        $result = $builder
            ->innerJoin('cv.product_collection_segment', 's')
            ->select('cv.id, s.definition')
            ->where($builder->expr()->in('cv.id', ':variantIds'))
            ->setParameter('variantIds', array_unique($variantIds))
            ->getQuery()
            ->getArrayResult();

        $manuallyAddedByVariantId = [];
        foreach ($result as $item) {
            $definitionParts = $this->productCollectionDefinitionConverter->getDefinitionParts($item['definition']);
            $manuallyAddedByVariantId[$item['id']] = array_filter(array_map(
                'intval',
                explode(',', (string)$definitionParts[ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY])
            ));
        }

        return $manuallyAddedByVariantId;
    }

    protected function filterManuallyAddedRecords(array &$variantsByRecordId, array $manuallyAddedByVariantId)
    {
        // Filter out variants that were assigned not as manually added products or not for product collection variant.
        foreach ($variantsByRecordId as $recordId => $variantIds) {
            foreach ($variantIds as $key => $variantId) {
                if (empty($manuallyAddedByVariantId[$variantId])
                    || !in_array($recordId, $manuallyAddedByVariantId[$variantId], true)
                ) {
                    unset($variantsByRecordId[$recordId][$key]);
                }
            }
        }
    }
}

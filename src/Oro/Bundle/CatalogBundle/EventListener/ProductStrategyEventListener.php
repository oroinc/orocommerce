<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\ORM\NonUniqueResultException;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\Mapper\CategoryPathMapperInterface;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Field\FieldHeaderHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration as ProductConfiguration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Set a category to the product after product import processed.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProductStrategyEventListener extends AbstractProductImportEventListener
{
    protected ?ConfigManager $configManager = null;
    protected ?MasterCatalogRootProviderInterface $masterCatalogRootProvider = null;
    protected ?FieldHeaderHelper $fieldHeaderHelper = null;
    protected ?FieldHelper $fieldHelper = null;
    protected ?AclHelper $aclHelper = null;
    protected ?CategoryPathMapperInterface $categoryPathMapper = null;

    protected ?bool $preserveOldBehavior = null;

    protected ?Category $masterCatalogRoot = null;
    protected ?string $categoryIdColumnHeader = null;
    protected ?bool $categoryPathIsExported = null;
    protected ?bool $categoryIdIsExported = null;
    protected ?string $mismatchResolution = null;
    protected ?string $nonUniqueTitleResolution = null;

    /** @var Category[] */
    protected array $categoriesByPath = [];

    public function setConfigManager(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    public function setMasterCatalogRootProvider(MasterCatalogRootProviderInterface $masterCatalogRootProvider): void
    {
        $this->masterCatalogRootProvider = $masterCatalogRootProvider;
    }

    public function setFieldHeaderHelper(FieldHeaderHelper $fieldHeaderHelper): void
    {
        $this->fieldHeaderHelper = $fieldHeaderHelper;
    }

    public function setFieldHelper(FieldHelper $fieldHelper): void
    {
        $this->fieldHelper = $fieldHelper;
    }

    public function setAclHelper(AclHelper $aclHelper): void
    {
        $this->aclHelper = $aclHelper;
    }

    public function setCategoryPathMapper(CategoryPathMapperInterface $categoryPathMapper): void
    {
        $this->categoryPathMapper = $categoryPathMapper;
    }

    protected function oldOnProcessAfter(ProductStrategyEvent $event)
    {
        $rawData = $event->getRawData();
        if (empty($rawData[self::CATEGORY_KEY])) {
            return;
        }

        $product = $event->getProduct();

        $category = $this->getCategoryByDefaultTitle($rawData[self::CATEGORY_KEY]);

        if ($category) {
            $product->setCategory($category);
        }
    }

    #[\Override]
    public function onClear()
    {
        parent::onClear();
        $this->categoriesByPath = [];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function onProcessAfter(ProductStrategyEvent $event)
    {
        if ($this->preserveOldBehavior()) {
            // (0) Work as before for backward compatibility
            $this->oldOnProcessAfter($event);
            return;
        }

        $rawData = $event->getRawData();
        $product = $event->getProduct();

        $categoryById = $product->getCategory();

        $path = $rawData[self::CATEGORY_PATH_KEY] ?? null;
        $defaultTitle = $rawData[self::CATEGORY_KEY] ?? null;

        // We treat empty strings as "no input" (null) as well.
        $path = ('' === $path) ? null : $path;
        $defaultTitle = ('' === $defaultTitle) ? null : $defaultTitle;

        $pathOrTitle = $path ?? $defaultTitle;

        if ($categoryById
            && (
                $this->getMismatchResolution() === ProductConfiguration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_ID_WINS
                || null === $pathOrTitle
            )
        ) {
            // (1) Category by ID is already set, and ID takes precedence - nothing to do
            // OR (2) Category by ID is already set, and we have no path nor title to match - nothing to do either
            return;
        }

        if (null === $pathOrTitle) {
            // (3) Nothing to do: we have no category by ID, no title, no path - not assigning any category then.
            return;
        }

        $categoryByPathOrTitle = $this->findCategoryByPathOrTitle(
            $event,
            $path,
            $defaultTitle,
            suggestId: (null === $categoryById)
        );

        if (null === $categoryByPathOrTitle) {
            // The errors were already reported by findCategoryByPathOrTitle():
            //  - (4) Searched by the path/title, failed on non-unique
            //  - (5) Searched by the path/title, not found
            return;
        }

        if (null === $categoryById) {
            // (6) Category by ID is not known, but we have $categoryByTitleOrPath
            $product->setCategory($categoryByPathOrTitle);
            return;
        }

        if ($categoryById->getId() === $categoryByPathOrTitle->getId()) {
            // (7) Category by ID and title are the same, so nothing to do
            return;
        }

        // We have both $categoryById and $categoryByPathOrTitle, and they are different.
        $this->handleIdTitleMismatch(
            $product,
            $categoryByPathOrTitle,
            $path,
            $defaultTitle,
            $categoryById,
            $event
        );
    }

    /**
     * Defensive check for backward compatibility with import customizations.
     * For the new configuration options to work, these dependencies need to be set.
     */
    protected function preserveOldBehavior(): bool
    {
        return $this->preserveOldBehavior ??=
            (
                null === $this->configManager
                || null === $this->masterCatalogRootProvider
                || null === $this->fieldHeaderHelper
                || null === $this->fieldHelper
                || null === $this->aclHelper
                || null === $this->categoryPathMapper
            );
    }

    protected function findCategoryByPathOrTitle(
        ProductStrategyEvent $event,
        ?string $path,
        ?string $defaultTitle,
        bool $suggestId
    ): ?Category {
        // Prefer the category path when present.
        if (null !== $path) {
            $pathTitles = $this->categoryPathMapper->pathStringToTitles($path);
            $categoryByPath = $this->findCategoryOrReportError(
                $event,
                categoryLabel: $path,
                suggestPath: false,
                suggestId: $suggestId,
                qbFactory: fn () => $this->getCategoryRepository()->findByTitlesPathQueryBuilder(
                    $pathTitles,
                    $this->getMasterCatalogRoot()
                )
            );

            if (null !== $categoryByPath) {
                return $categoryByPath;
            }

            // If a category path is provided but cannot be resolved, do NOT fall back to title lookup.
            // The path is assumed to be the authoritative identifier; falling back to the title could
            // silently mask hard-to-track user errors (e.g., typos or incorrect copy-paste) and
            // lead to unintended category assignments.
            // To resolve categories by title only, the path column must be left empty.

            // Exit early.
            return null;
        }

        if (null !== $defaultTitle) {
            $categoryByTitle = $this->findCategoryOrReportError(
                $event,
                categoryLabel: $defaultTitle,
                suggestPath: true,
                suggestId: $suggestId,
                qbFactory: fn () => $this->getCategoryRepository()->findByDefaultTitleQueryBuilder($defaultTitle)
            );

            if (null !== $categoryByTitle) {
                return $categoryByTitle;
            }
        }

        return null;
    }

    /**
     * @param callable $qbFactory pass a factory instead of a qb instance to avoid dealing with a mutated qb instance
     *                              after calling this method
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function findCategoryOrReportError(
        ProductStrategyEvent $event,
        string $categoryLabel,
        bool $suggestPath,
        bool $suggestId,
        callable $qbFactory
    ): ?Category {
        $qb = $qbFactory();

        switch ($this->getNonUniqueTitleResolution()) {
            case ProductConfiguration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST:
                $qb->setMaxResults(1); // ensure it never throws.
                $query = $this->aclHelper ? $this->aclHelper->apply($qb) : $qb->getQuery();
                /** @noinspection PhpUnhandledExceptionInspection */
                $category = $query->getOneOrNullResult();
                break;

            case ProductConfiguration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FAIL:
                $query = $this->aclHelper ? $this->aclHelper->apply($qb) : $qb->getQuery();
                try {
                    $category = $query->getOneOrNullResult();
                } catch (NonUniqueResultException) {
                    // (4) Failed on not unique
                    $this->reportImportError(
                        $event,
                        \sprintf('Category "%s" is not unique in the master catalog.', $categoryLabel),
                        suggestPath: $suggestPath,
                        suggestId: $suggestId
                    );
                    return null;
                }
                break;

            default:
                throw new \LogicException(\sprintf(
                    'Unknown category non-unique title resolution: %s.',
                    $this->getNonUniqueTitleResolution()
                ));
        }

        if (null === $category) {
            // (5) Searched by path or title, not found
            $this->reportImportError(
                $event,
                \sprintf('Category "%s" not found in the master catalog.', $categoryLabel),
                suggestPath: $suggestPath,
                suggestId: $suggestId
            );
            return null;
        }

        return $category;
    }

    protected function reportImportError(
        ProductStrategyEvent $event,
        string $message,
        bool $suggestPath,
        bool $suggestId
    ): void {
        $message = $this->addErrorMessageSuggestions($message, $suggestPath, $suggestId);
        $event->markProductInvalid();
        $event->getContext()->incrementErrorEntriesCount();
        $event->getContext()->addError($message);
    }

    protected function addErrorMessageSuggestions(string $message, bool $suggestPath, bool $suggestId): string
    {
        $suggestions = '';
        if ($this->isCategoryPathExported() && $suggestPath) {
            $suggestions .= \sprintf(
                ' Specify the full category path, like "All Products > Supplies > Parts"'
                . ' in the "%s" column to uniquely identify the category',
                self::CATEGORY_PATH_KEY
            );
        }
        if ($this->isCategoryIdExported() && $suggestId) {
            $suggestions .= \sprintf(
                (empty($suggestions) ? ' Specify' : ', or specify')
                . ' the correct category ID in the "%s" column',
                $this->getCategoryIdColumnHeader()
            );
        }
        $message .= empty($suggestions) ? '' : ($suggestions . '.');

        return $message;
    }

    protected function handleIdTitleMismatch(
        Product $product,
        ?Category $categoryByPathOrTitle,
        ?string $path,
        ?string $title,
        Category $categoryById,
        ProductStrategyEvent $event
    ): void {
        switch ($this->getMismatchResolution()) {
            case ProductConfiguration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_ID_WINS:
                // Technically, we would not even get here as it is handled much earlier in (1), but let's be explicit.
                return;

            case ProductConfiguration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS:
                // (8) Mismatch, path/title wins
                $product->setCategory($categoryByPathOrTitle);
                return;

            case ProductConfiguration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_FAIL:
                // (9) Mismatch, fail
                $this->reportImportError(
                    $event,
                    \sprintf(
                        'Category %s "%s" does not match the category with ID %d ("%s").',
                        (null !== $path) ? 'path' : 'title',
                        $path ?? $title,
                        $categoryById->getId(),
                        $categoryById->getDefaultTitle()
                    ),
                    suggestPath: (null === $path),
                    suggestId: false
                );
                return;

            default:
                throw new \LogicException(
                    \sprintf('Unknown category ID/title mismatch resolution: %s.', $this->getMismatchResolution())
                );
        }
    }

    protected function isCategoryPathExported(): bool
    {
        return $this->categoryPathIsExported ??=
            (bool) $this->configManager->get(
                ProductConfiguration::getConfigKeyByName(ProductConfiguration::EXPORT_CATEGORY_PATH)
            );
    }

    protected function isCategoryIdExported(): bool
    {
        return $this->categoryIdIsExported ??=
            !$this->fieldHelper->getConfigValue(
                Product::class,
                'category',
                'excluded',
                false
            );
    }

    protected function getCategoryIdColumnHeader(): string
    {
        return $this->categoryIdColumnHeader ??=
            $this->fieldHeaderHelper->buildRelationFieldHeader(
                Product::class,
                ProductDataConverterEventListener::PRODUCT_CATEGORY_FIELD_NAME,
                Category::class,
                ProductDataConverterEventListener::CATEGORY_ID_FIELD_NAME
            );
    }

    protected function getMasterCatalogRoot(): ?Category
    {
        return $this->masterCatalogRoot ??=
            $this->masterCatalogRootProvider->getMasterCatalogRoot();
    }

    protected function getNonUniqueTitleResolution(): string
    {
        return $this->nonUniqueTitleResolution ??=
            (string) $this->configManager->get(
                ProductConfiguration::getConfigKeyByName(
                    ProductConfiguration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION
                )
            );
    }

    protected function getMismatchResolution(): string
    {
        return $this->mismatchResolution ??=
            (string) $this->configManager->get(
                ProductConfiguration::getConfigKeyByName(
                    ProductConfiguration::IMPORT_CATEGORY_MISMATCH_RESOLUTION
                )
            );
    }
}

<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Normalizer;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

/**
 * Normalizer for Category.
 * Additionally is responsible for:
 * - excludes organization from normalized category.
 */
class CategoryNormalizer extends ConfigurableEntityNormalizer
{
    /** @var CategoryImportExportHelper */
    private $categoryImportExportHelper;

    public function setCategoryImportExportHelper(CategoryImportExportHelper $categoryImportExportHelper): void
    {
        $this->categoryImportExportHelper = $categoryImportExportHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return is_a($data, Category::class);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_a($type, Category::class, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param Category $object
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $normalizedCategory = parent::normalize($object, $format, $context);

        if ($this->getMode($context) === self::FULL_MODE && $parentCategory = $object->getParentCategory()) {
            // Adds parentCategory title to normalized category data.
            $normalizedCategory['parentCategory']['titles']['default']['string']
                = $this->categoryImportExportHelper->getPersistedCategoryPath($parentCategory);
        }

        return $normalizedCategory;
    }

    /**
     * {@inheritdoc}
     */
    protected function isFieldSkippedForNormalization($entityName, $fieldName, array $context)
    {
        return parent::isFieldSkippedForNormalization($entityName, $fieldName, $context)
            || $this->isOrganizationSkippedForNormalization($entityName, $fieldName, $context);
    }

    protected function isOrganizationSkippedForNormalization(
        string $entityName,
        string $fieldName,
        array $context
    ): bool {
        return $entityName === Category::class && $fieldName === 'organization';
    }
}

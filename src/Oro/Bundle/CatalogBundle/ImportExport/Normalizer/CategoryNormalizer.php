<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Normalizer;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\ManagerRegistry;
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
    private CategoryImportExportHelper $categoryImportExportHelper;

    private ManagerRegistry $doctrine;

    public function setCategoryImportExportHelper(CategoryImportExportHelper $categoryImportExportHelper): void
    {
        $this->categoryImportExportHelper = $categoryImportExportHelper;
    }

    /**
     * @param ManagerRegistry $doctrine
     */
    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
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
        $object = $this->revitalizeObject($object);

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

    /**
     * Why a category need to re-vitalize is because during the export, it executes batch by batch
     * and manager will do clear per batch. the parent category of a category might be cleared
     * before it been accessed as a parent category.
     * To fetch them with EAGER mode could prevent them to load as Proxy implementation.
     * @param Category $object
     * @return Category
     */
    private function revitalizeObject($object): Category
    {
        if ($object instanceof Category && $object->getParentCategory() instanceof Proxy) {
            $parentCategory = $object->getParentCategory();

            if ($parentCategory->__isInitialized__ === false) {
                $parentCategory->__load();
            }

            if (!$parentCategory->getId()) {
                return $this->doctrine
                    ->getRepository(Category::class)
                    ->getCategoryEagerMode($object);
            }
        }

        return $object;
    }
}

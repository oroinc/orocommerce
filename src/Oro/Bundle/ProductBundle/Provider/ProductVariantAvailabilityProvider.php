<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class ProductVariantAvailabilityProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityFieldProvider */
    protected $entityFieldProvider;

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityFieldProvider $entityFieldProvider
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityFieldProvider $entityFieldProvider,
        EnumValueProvider $enumValueProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityFieldProvider = $entityFieldProvider;
        $this->enumValueProvider = $enumValueProvider;
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return array
     */
    public function getVariantFieldsWithAvailability(Product $configurableProduct, $variantParameters = [])
    {
        $variantFields = $configurableProduct->getVariantFields();

        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Product::class);

        $availableSimpleProducts = $repository->findSimpleProductsByVariantFields(
            $configurableProduct,
            $variantParameters
        );

        $converter = new CamelCaseToSnakeCaseNameConverter();

        $allVariants = [];
        foreach ($variantFields as $variantField) {
            // get array of all variants
            $allVariants[$variantField] = $this->getAllVariantsByVariantFieldName($variantField);

            // update array of all variants with available variants
            $getterSnakeCase = sprintf('get_%s', $variantField);
            $getterMethod = $converter->denormalize($getterSnakeCase);
            $fieldType = $this->getFieldType($variantField);

            foreach ($availableSimpleProducts as $simpleProduct) {
                $variantValue = $simpleProduct->$getterMethod();

                switch ($fieldType) {
                    case 'enum':
                        $allVariants[$variantField][$variantValue->getId()] = true;
                        break;

                    case 'boolean':
                        $allVariants[$variantField][$variantValue] = true;
                        break;
                }
            }
        }

        return $allVariants;
    }

    /**
     * @param string $variantFieldName
     * @return array
     */
    protected function getAllVariantsByVariantFieldName($variantFieldName)
    {
        $type = $this->getFieldType($variantFieldName);

        $allVariants = [];
        switch ($type) {
            case 'enum':
                $enumCode = ExtendHelper::generateEnumCode(Product::class, $variantFieldName);
                $allVariants = $this->enumValueProvider->getEnumChoicesByCode($enumCode);
                foreach ($allVariants as &$variant) {
                    $variant = false;
                }
                break;

            case 'boolean':
                $allVariants = [
                    false => false,
                    true => false,
                ];
                break;
        }

        return $allVariants;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function getFieldType($fieldName)
    {
        $allFields = $this->entityFieldProvider->getFields(Product::class, true);
        $type = '';
        foreach ($allFields as $field) {
            if ($field['name'] === $fieldName) {
                $type = $field['type'];
                break;
            }
        }

        return $type;
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * $variantParameters = [
     *     'size' => 'm',
     *     'color' => 'red',
     *     'slim_fit' => true
     * ]
     * Value is extended field id for select field and true or false for boolean field
     * @return Product[]
     */
    public function getSimpleProductsByVariantFields(Product $configurableProduct, $variantParameters = [])
    {
        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Product::class);

        return $repository->findSimpleProductsByVariantFields($configurableProduct, $variantParameters);
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return Product
     * @throws InvalidArgumentException
     */
    public function getSimpleProductByVariantFields(Product $configurableProduct, $variantParameters)
    {
        $simpleProducts = $this->getSimpleProductsByVariantFields($configurableProduct, $variantParameters);

        if (count($simpleProducts) !== 1) {
            throw new InvalidArgumentException('Variant values provided don\'t match exactly one simple product');
        }

        return $simpleProducts[0];
    }
}

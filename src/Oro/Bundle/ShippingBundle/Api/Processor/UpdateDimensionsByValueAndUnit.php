<?php

namespace Oro\Bundle\ShippingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;

/**
 * Sets dimensions based on "dimensionsLength", "dimensionsWidth", "dimensionsHeight"
 * and "dimensionsUnit" field if they are submitted.
 * It is expected that an entity for which this processor is used
 * has "getDimensions()" and "setDimensions(Dimensions $dimensions)" methods.
 */
class UpdateDimensionsByValueAndUnit extends AbstractUpdateNestedModel
{
    protected DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->modelPropertyPath = "dimensions";
    }

    protected function processPreSubmit(CustomizeFormDataContext $context): void
    {
        /** @var array $data */
        $data = $context->getData();
        /** @var ProductShippingOptions $entity */
        $entity = $context->getForm()->getData();

        $dimensionsFields = ['length', 'width', 'height', 'unit'];
        $dimensions = [];
        $isSubmitted = false;
        foreach ($dimensionsFields as $dimension) {
            $fieldName = $context->findFormFieldName('dimensions' . ucfirst($dimension));

            if (null !== $fieldName && array_key_exists($fieldName, $data)) {
                $isSubmitted = true;
                $dimensions[$dimension] = $data[$fieldName];

                if ($dimension === 'unit' && array_key_exists('id', $data[$fieldName])) {
                    $repository = $this->doctrineHelper->getEntityRepository(LengthUnit::class);
                    $dimensions[$dimension] = $repository->find(strtolower($dimensions[$dimension]['id']));
                }
            } else {
                // false here means field isn't submitted
                $dimensions[$dimension] = false;
            }
        }

        if ($isSubmitted) {
            $dimensions = $this->getDimensionsDefaultValues($dimensions, $entity);

            $entity->setDimensions(Dimensions::create(
                $dimensions['length'],
                $dimensions['width'],
                $dimensions['height'],
                (empty($dimensions['unit']) ? null : $dimensions['unit'])
            ));
        }
    }

    private function getDimensionsDefaultValues(array $dimensions, ProductShippingOptionsInterface $entity): array
    {
        foreach ($dimensions as $key => $value) {
            if ($value === false) {
                if ($key === 'unit') {
                    $dimensions[$key] = null !== $entity->getDimensions() ? $entity->getDimensions()->getUnit() : null;
                } elseif ($entity->getDimensions()) {
                    $method = 'get' . ucfirst($key);
                    $dimensions[$key] = $entity->getDimensions()->getValue()
                        ? $entity->getDimensions()->getValue()->$method() : null;
                }
            }
        }

        return $dimensions;
    }
}

<?php

namespace Oro\Bundle\ShippingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
 * Sets weight based on "weightValue" and "weightUnit" field if they are submitted.
 * It is expected that an entity for which this processor is used
 * has "getWeight()" and "setWeight(Weight $weight)" methods.
 */
class UpdateWeightByValueAndUnit extends AbstractUpdateNestedModel
{
    protected DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->modelPropertyPath = "weight";
    }

    protected function processPreSubmit(CustomizeFormDataContext $context): void
    {
        /** @var ProductShippingOptions $entity */
        $entity = $context->getForm()->getData();

        $weightValue = $this->getWeightValue($context);
        $weightUnit = $this->getWeightUnit($context);

        if ($weightValue !== false || $weightUnit !== false) {
            if ($weightValue === false) {
                $weightValue = null !== $entity->getWeight() ? $entity->getWeight()->getValue() : null;
            }

            if ($weightUnit === false) {
                $weightUnit = null !== $entity->getWeight() ? $entity->getWeight()->getUnit() : null;
            }

            $entity->setWeight(Weight::create($weightValue, $weightUnit));
        }
    }

    private function getWeightValue(CustomizeFormDataContext $context)
    {
        $data = $context->getData();
        $weightValue = false; // false here means field isn't submitted

        $valueFieldName = $context->findFormFieldName('weightValue');
        if (null !== $valueFieldName && array_key_exists($valueFieldName, $data)) {
            $weightValue = $data[$valueFieldName];
        }

        return $weightValue;
    }

    /**
     * @param CustomizeFormDataContext $context
     * @return bool|null|WeightUnit
     */
    private function getWeightUnit(CustomizeFormDataContext $context)
    {
        $data = $context->getData();
        $weightUnit = false; // false here means field isn't submitted

        $unitFieldName = $context->findFormFieldName('weightUnit');
        if (null !== $unitFieldName
            && array_key_exists($unitFieldName, $data)
            && array_key_exists('id', $data[$unitFieldName])
        ) {
            $repository = $this->doctrineHelper->getEntityRepository(WeightUnit::class);
            $weightUnit = $repository->find(strtolower($data[$unitFieldName]['id']));
        }

        return $weightUnit;
    }
}

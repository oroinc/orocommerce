<?php

namespace Oro\Bundle\ShippingBundle\Api\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets weight based on "weightValue" and "weightUnit" field if they are submitted.
 * It is expected that an entity for which this processor is used
 * has "getWeight()" and "setWeight(Weight $weight)" methods.
 */
class UpdateWeightByValueAndUnit implements ProcessorInterface
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        switch ($context->getEvent()) {
            case CustomizeFormDataContext::EVENT_PRE_SUBMIT:
                $this->processPreSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_VALIDATE:
                FormUtil::fixValidationErrorPropertyPathForExpandedProperty($context->getForm(), 'weight');
                break;
        }
    }

    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        /** @var array $data */
        $data = $context->getData();

        [$value, $isValueSubmitted] = $this->getSubmittedValue($data, $context->findFormFieldName('weightValue'));
        [$unit, $isUnitSubmitted] = $this->getSubmittedWeightUnit($data, $context->findFormFieldName('weightUnit'));

        if ($isValueSubmitted || $isUnitSubmitted) {
            /** @var ProductShippingOptions $entity */
            $entity = $context->getForm()->getData();
            $entityWeight = $entity->getWeight();
            if (null !== $entityWeight) {
                if (!$isValueSubmitted) {
                    $value = $entityWeight->getValue();
                }
                if (!$isUnitSubmitted) {
                    $unit = $entityWeight->getUnit();
                }
            }
            $entity->setWeight(Weight::create($value, $unit));
        }
    }

    private function getSubmittedValue(array $data, ?string $formFieldName): array
    {
        $value = null;
        $isValueSubmitted = false;
        if (null !== $formFieldName && \array_key_exists($formFieldName, $data)) {
            $value = $data[$formFieldName];
            $isValueSubmitted = true;
        }

        return [$value, $isValueSubmitted];
    }

    private function getSubmittedWeightUnit(array $data, ?string $formFieldName): array
    {
        $unit = null;
        $isUnitSubmitted = false;
        if (null !== $formFieldName
            && \array_key_exists($formFieldName, $data)
            && \array_key_exists('id', $data[$formFieldName])
        ) {
            $unit = $this->doctrine->getRepository(WeightUnit::class)
                ->find(strtolower($data[$formFieldName]['id']));
            $isUnitSubmitted = true;
        }

        return [$unit, $isUnitSubmitted];
    }
}

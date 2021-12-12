<?php

namespace Oro\Bundle\ShippingBundle\Api\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets dimensions based on "dimensionsLength", "dimensionsWidth", "dimensionsHeight"
 * and "dimensionsUnit" field if they are submitted.
 * It is expected that an entity for which this processor is used
 * has "getDimensions()" and "setDimensions(Dimensions $dimensions)" methods.
 */
class UpdateDimensionsByValueAndUnit implements ProcessorInterface
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
                FormUtil::fixValidationErrorPropertyPathForExpandedProperty($context->getForm(), 'dimensions');
                break;
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        /** @var array $data */
        $data = $context->getData();

        [$length, $isLengthSubmitted] = $this->getSubmittedValue(
            $data,
            $context->findFormFieldName('dimensionsLength')
        );
        [$width, $isWidthSubmitted] = $this->getSubmittedValue(
            $data,
            $context->findFormFieldName('dimensionsWidth')
        );
        [$height, $isHeightSubmitted] = $this->getSubmittedValue(
            $data,
            $context->findFormFieldName('dimensionsHeight')
        );
        [$unit, $isUnitSubmitted] = $this->getSubmittedUnit(
            $data,
            $context->findFormFieldName('dimensionsUnit')
        );

        if ($isLengthSubmitted || $isWidthSubmitted || $isHeightSubmitted || $isUnitSubmitted) {
            /** @var ProductShippingOptions $entity */
            $entity = $context->getForm()->getData();
            $entityDimensions = $entity->getDimensions();
            if (null !== $entityDimensions) {
                $entityDimensionsValue = $entityDimensions->getValue();
                if (null !== $entityDimensionsValue) {
                    if (!$isLengthSubmitted) {
                        $length = $entityDimensionsValue->getLength();
                    }
                    if (!$isWidthSubmitted) {
                        $width = $entityDimensionsValue->getWidth();
                    }
                    if (!$isHeightSubmitted) {
                        $height = $entityDimensionsValue->getHeight();
                    }
                }
                $entityDimensionsUnit = $entityDimensions->getUnit();
                if (null !== $entityDimensionsUnit && !$isUnitSubmitted) {
                    $unit = $entityDimensionsUnit;
                }
            }
            $entity->setDimensions(Dimensions::create($length, $width, $height, $unit));
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

    private function getSubmittedUnit(array $data, ?string $formFieldName): array
    {
        $unit = null;
        $isUnitSubmitted = false;
        if (null !== $formFieldName
            && \array_key_exists($formFieldName, $data)
            && \array_key_exists('id', $data[$formFieldName])
        ) {
            $unit = $this->doctrine->getRepository(LengthUnit::class)
                ->find(strtolower($data[$formFieldName]['id']));
            $isUnitSubmitted = true;
        }

        return [$unit, $isUnitSubmitted];
    }
}

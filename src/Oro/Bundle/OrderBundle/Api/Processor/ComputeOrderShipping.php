<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "shippingCostAmount" and "shippingMethod" fields for Order entity.
 */
class ComputeOrderShipping implements ProcessorInterface
{
    private const SHIPPING_METHOD_FIELD_NAME = 'shippingMethod';
    private const SHIPPING_COST_FIELD_NAME = 'shippingCostAmount';

    private DoctrineHelper $doctrineHelper;
    private ShippingMethodLabelTranslator $shippingMethodLabelTranslator;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ShippingMethodLabelTranslator $shippingMethodLabelTranslator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->shippingMethodLabelTranslator = $shippingMethodLabelTranslator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if ($context->isFieldRequested(self::SHIPPING_COST_FIELD_NAME)) {
            $overriddenShippingCost = $context->getResultFieldValue('overriddenShippingCostAmount', $data);
            if (null !== $overriddenShippingCost) {
                $data[self::SHIPPING_COST_FIELD_NAME] = $overriddenShippingCost;
            }
        }

        if ($context->isFieldRequested(self::SHIPPING_METHOD_FIELD_NAME, $data)) {
            $code = $context->getResultFieldValue('shippingMethod', $data);
            $type = $context->getResultFieldValue('shippingMethodType', $data);
            $shippingMethod = null;
            if (null !== $code) {
                $shippingMethod = [
                    'code'  => $code,
                    'label' => $this->getShippingMethodLabel(
                        $code,
                        $type,
                        $context->getResultFieldValueByPropertyPath('organization.id', $data)
                    )
                ];
            }
            $data[self::SHIPPING_METHOD_FIELD_NAME] = $shippingMethod;
        }

        $context->setData($data);
    }

    private function getShippingMethodLabel(?string $code, ?string $type, ?int $organizationId): ?string
    {
        return $this->shippingMethodLabelTranslator->getShippingMethodWithTypeLabel(
            $code,
            $type,
            null !== $organizationId
                ? $this->doctrineHelper->getEntityReference(Organization::class, $organizationId)
                : null
        );
    }
}

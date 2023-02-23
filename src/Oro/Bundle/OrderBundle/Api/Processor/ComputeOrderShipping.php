<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
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

    private ShippingMethodLabelTranslator $shippingMethodLabelTranslator;

    public function __construct(ShippingMethodLabelTranslator $shippingMethodLabelTranslator)
    {
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
            if (null !== $code || null !== $type) {
                $shippingMethod = [
                    'code'  => $code,
                    'label' => $this->getShippingMethodLabel($code, $type)
                ];
            }
            $data[self::SHIPPING_METHOD_FIELD_NAME] = $shippingMethod;
        }

        $context->setData($data);
    }

    private function getShippingMethodLabel(?string $code, ?string $type): ?string
    {
        if (null === $code || null === $type) {
            return null;
        }

        return $this->shippingMethodLabelTranslator->getShippingMethodWithTypeLabel($code, $type);
    }
}

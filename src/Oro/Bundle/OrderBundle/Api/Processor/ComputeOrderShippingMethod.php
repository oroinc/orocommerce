<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "shippingMethod" field for Order entity.
 */
class ComputeOrderShippingMethod implements ProcessorInterface
{
    private const FIELD_NAME = 'shippingMethod';

    /** @var ShippingMethodLabelTranslator|null */
    private $shippingMethodLabelTranslator;

    /**
     * @param ShippingMethodLabelTranslator|null $shippingMethodLabelTranslator
     */
    public function __construct(ShippingMethodLabelTranslator $shippingMethodLabelTranslator = null)
    {
        $this->shippingMethodLabelTranslator = $shippingMethodLabelTranslator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        if (!$context->isFieldRequested(self::FIELD_NAME, $data)) {
            return;
        }

        $code = $context->getResultFieldValue('shippingMethod', $data);
        $type = $context->getResultFieldValue('shippingMethodType', $data);
        $shippingMethod = null;
        if (null !== $code || null !== $type) {
            $shippingMethod = [
                'code'  => $code,
                'label' => $this->getShippingMethodLabel($code, $type)
            ];
        }
        $data[self::FIELD_NAME] = $shippingMethod;
        $context->setResult($data);
    }

    /**
     * @param string|null $code
     * @param string|null $type
     *
     * @return string|null
     */
    private function getShippingMethodLabel(?string $code, ?string $type): ?string
    {
        if (null === $code || null === $type) {
            return null;
        }

        if (null === $this->shippingMethodLabelTranslator) {
            return $code . ', ' . $type;
        }

        return $this->shippingMethodLabelTranslator->getShippingMethodWithTypeLabel($code, $type);
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Removes the submitted order line item price ("price" and "currency" fields)
 * from submitted data if it equals to NULL.
 * It is required to avoid incorrect validation result, as these properties are optional.
 */
class HandleOrderLineItemEmptyPrice implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $data = $context->getData();
        $priceForm = $context->findFormField('value');
        if (null !== $priceForm && $this->isSubmittedAndNull($data, $priceForm)) {
            unset($data[$priceForm->getName()]);
        }
        $currencyForm = $context->findFormField('currency');
        if (null !== $currencyForm && $this->isSubmittedAndNull($data, $currencyForm)) {
            unset($data[$currencyForm->getName()]);
        }
        $context->setData($data);
    }

    private function isSubmittedAndNull(array $data, FormInterface $fieldForm): bool
    {
        return
            \array_key_exists($fieldForm->getName(), $data)
            && null === $data[$fieldForm->getName()];
    }
}

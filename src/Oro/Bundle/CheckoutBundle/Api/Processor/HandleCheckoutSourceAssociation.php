<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles "source" association of Checkout entity for "create" actions.
 */
class HandleCheckoutSourceAssociation implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!FormUtil::isNotSubmittedOrSubmittedAndValid($form->get('source'))) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $form->getData();
        $checkoutSource = $checkout->getSource();
        if (null === $checkoutSource) {
            $checkoutSource = new CheckoutSource();
            $checkout->setSource($checkoutSource);
        }
    }
}

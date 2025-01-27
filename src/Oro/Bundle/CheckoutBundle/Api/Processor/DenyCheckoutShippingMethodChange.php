<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Repository\CheckoutLineItemGroupRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Denies changing of the shipping method and the shipping method type
 * when the checkout shipping type in not equal to the given shipping type.
 */
class DenyCheckoutShippingMethodChange implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly CheckoutLineItemGroupRepository $checkoutLineItemGroupRepository,
        private readonly string $shippingType
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if ($this->checkoutLineItemGroupRepository->getShippingType() === $this->shippingType) {
            return;
        }

        $this->validateFieldValueChange($context, 'shippingMethod');
        $this->validateFieldValueChange($context, 'shippingMethodType');
    }

    private function validateFieldValueChange(CustomizeFormDataContext $context, string $fieldName): void
    {
        $fieldForm = $context->findFormField($fieldName);
        if (null !== $fieldForm
            && FormUtil::isSubmittedAndValid($fieldForm)
            && $this->isFieldValueChanged($context, $fieldForm)
        ) {
            $this->addShippingMethodChangeFormError($fieldForm);
        }
    }

    private function isFieldValueChanged(CustomizeFormDataContext $context, FormInterface $fieldForm): bool
    {
        $originalData = $this->doctrineHelper->getEntityManagerForClass(Checkout::class)
            ->getUnitOfWork()
            ->getOriginalEntityData($context->getData());
        $existingValue = $originalData[(string)$fieldForm->getPropertyPath()] ?? null;

        return $fieldForm->getData() !== $existingValue;
    }

    private function addShippingMethodChangeFormError(FormInterface $form): void
    {
        FormUtil::addNamedFormError(
            $form,
            'shipping method change constraint',
            \sprintf('This value can be changed only when the shipping type is "%s".', $this->shippingType)
        );
    }
}

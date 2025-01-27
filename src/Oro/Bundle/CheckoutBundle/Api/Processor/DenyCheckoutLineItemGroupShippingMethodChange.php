<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutLineItemGroup;
use Oro\Bundle\CheckoutBundle\Api\Repository\CheckoutLineItemGroupRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Denies changing of the shipping method and the shipping method type for the checkout line item group
 * when the checkout shipping type in not equal to the given shipping type.
 */
class DenyCheckoutLineItemGroupShippingMethodChange implements ProcessorInterface
{
    private const string SHIPPING_TYPE = CheckoutLineItemGroupRepository::SHIPPING_TYPE_LINE_ITEM_GROUP;

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly CheckoutLineItemGroupRepository $checkoutLineItemGroupRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if ($this->checkoutLineItemGroupRepository->getShippingType() === self::SHIPPING_TYPE) {
            return;
        }

        $this->validateFieldValueChange($context, 'shippingMethod', 'method');
        $this->validateFieldValueChange($context, 'shippingMethodType', 'type');
    }

    private function validateFieldValueChange(
        CustomizeFormDataContext $context,
        string $fieldName,
        string $attributeName
    ): void {
        $fieldForm = $context->findFormField($fieldName);
        if (null !== $fieldForm
            && FormUtil::isSubmittedAndValid($fieldForm)
            && $this->isFieldValueChanged($context, $fieldForm, $attributeName)
        ) {
            $this->addShippingMethodChangeFormError($fieldForm);
        }
    }

    private function isFieldValueChanged(
        CustomizeFormDataContext $context,
        FormInterface $fieldForm,
        string $attributeName
    ): bool {
        /** @var CheckoutLineItemGroup $group */
        $group = $context->getData();
        $em = $this->doctrineHelper->getEntityManagerForClass(Checkout::class);
        $checkout = $em->find(Checkout::class, $group->getCheckoutId());
        if (null === $checkout) {
            return false;
        }

        $originalData = $em->getUnitOfWork()->getOriginalEntityData($checkout);
        $originalLineItemGroupShippingData = $originalData['lineItemGroupShippingData'] ?? [];
        $existingValue = $originalLineItemGroupShippingData[$group->getGroupKey()][$attributeName] ?? null;

        return $fieldForm->getData() !== $existingValue;
    }

    private function addShippingMethodChangeFormError(FormInterface $form): void
    {
        FormUtil::addNamedFormError(
            $form,
            'shipping method change constraint',
            \sprintf('This value can be changed only when the shipping type is "%s".', self::SHIPPING_TYPE)
        );
    }
}

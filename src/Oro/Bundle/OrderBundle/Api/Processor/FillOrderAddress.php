<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that the submitted order address has a customer user address, a customer address
 * or filled in address fields only.
 * Fills the order address fields from the submitted customer user or customer address if any.
 */
class FillOrderAddress implements ProcessorInterface
{
    private const SUBMITTED_DATA = 'submitted_data';

    private OrderAddressManager $orderAddressManager;
    private TranslatorInterface $translator;

    public function __construct(OrderAddressManager $orderAddressManager, TranslatorInterface $translator)
    {
        $this->orderAddressManager = $orderAddressManager;
        $this->translator = $translator;
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
            case CustomizeFormDataContext::EVENT_PRE_VALIDATE:
                $this->processPreValidate($context);
                break;
        }
    }

    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        $context->set(self::SUBMITTED_DATA, $context->getData());
    }

    private function processPreValidate(CustomizeFormDataContext $context): void
    {
        /** @var OrderAddress $address */
        $address = $context->getData();
        if (!$this->isSubmittedDataValid($context)) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                'order address constraint',
                $this->translator->trans('oro.order.orderaddress.multiple', [], 'validators')
            );
        } elseif (null !== $address->getCustomerAddress()) {
            $this->orderAddressManager->updateFromAbstract($address->getCustomerAddress(), $address);
        } elseif (null !== $address->getCustomerUserAddress()) {
            $this->orderAddressManager->updateFromAbstract($address->getCustomerUserAddress(), $address);
        }
    }

    private function isSubmittedDataValid(CustomizeFormDataContext $context): bool
    {
        $submittedData = $context->get(self::SUBMITTED_DATA);
        $customerUserAddressFieldName = $context->findFormFieldName('customerUserAddress');
        $customerAddressFieldName = $context->findFormFieldName('customerAddress');
        $isCustomerUserAddressSubmitted = $this->isSubmitted($submittedData, $customerUserAddressFieldName);
        $isCustomerAddressSubmitted = $this->isSubmitted($submittedData, $customerAddressFieldName);

        if ($isCustomerUserAddressSubmitted && $isCustomerAddressSubmitted) {
            return false;
        }

        if ($isCustomerUserAddressSubmitted) {
            unset($submittedData[$customerUserAddressFieldName]);

            return $this->isArrayHasOnlyNullValues($submittedData);
        }

        if ($isCustomerAddressSubmitted) {
            unset($submittedData[$customerAddressFieldName]);

            return $this->isArrayHasOnlyNullValues($submittedData);
        }

        return true;
    }

    private function isSubmitted(array $submittedData, ?string $fieldName): bool
    {
        return
            null !== $fieldName
            && \array_key_exists($fieldName, $submittedData)
            && null !== $submittedData[$fieldName];
    }

    private function isArrayHasOnlyNullValues(array $data): bool
    {
        foreach ($data as $fieldValue) {
            if (null !== $fieldValue) {
                return false;
            }
        }

        return true;
    }
}

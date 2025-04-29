<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that the submitted quote address has a customer user address, a customer address
 * or filled in address fields only.
 * Fills the quote address fields from the submitted customer user or customer address if any.
 */
class FillQuoteAddress implements ProcessorInterface
{
    private const SUBMITTED_DATA = 'submitted_data';

    public function __construct(
        private readonly QuoteAddressManager $quoteAddressManager,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
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
        /** @var QuoteAddress $address */
        $address = $context->getData();
        if (!$this->isSubmittedDataValid($context)) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                'quote address constraint',
                $this->translator->trans('oro.sale.quoteaddress.multiple', [], 'validators')
            );
        } elseif (null !== $address->getCustomerAddress()) {
            $this->quoteAddressManager->updateFromAbstract($address->getCustomerAddress(), $address);
        } elseif (null !== $address->getCustomerUserAddress()) {
            $this->quoteAddressManager->updateFromAbstract($address->getCustomerUserAddress(), $address);
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
            return $this->isArrayHasOnlyNullValues($submittedData, [$customerUserAddressFieldName, 'quote']);
        }

        if ($isCustomerAddressSubmitted) {
            return $this->isArrayHasOnlyNullValues($submittedData, [$customerAddressFieldName, 'quote']);
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

    private function isArrayHasOnlyNullValues(array $data, array $skipFields): bool
    {
        foreach ($data as $fieldName => $fieldValue) {
            if (null !== $fieldValue && !\in_array($fieldName, $skipFields, true)) {
                return false;
            }
        }

        return true;
    }
}

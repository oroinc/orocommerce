<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Checks that translatable label fields are submitted during a creation of a product unit
 * and submitted value is not empty during a creation and modification of a product unit.
 */
class ValidateProductUnitLabelFields implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */
        $this->validateField('label', $context);
        $this->validateField('pluralLabel', $context);
        $this->validateField('shortLabel', $context);
        $this->validateField('shortPluralLabel', $context);
    }

    private function validateField(string $fieldName, CustomizeFormDataContext $context): void
    {
        $form = $context->findFormField($fieldName);
        if (null === $form) {
            return;
        }

        if ($context->getParentAction() === 'create' && !$form->isSubmitted()) {
            FormUtil::addFormConstraintViolation($form, new NotBlank());
        }

        if ($form->isSubmitted() && (null === $form->getData() || '' === $form->getData())) {
            FormUtil::addFormConstraintViolation($form, new NotBlank());
        }
    }
}

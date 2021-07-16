<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuickAddRowCollectionValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_quick_add_row_collection_validator';

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var QuickAddRow $quickAddRow */
        foreach ($value->getIterator() as $quickAddRow) {
            /** @var ConstraintViolationListInterface $violations */
            $violations = $this->validator->validate(
                $quickAddRow,
                null,
                new GroupSequence(['QuickAddRow', 'ProductUnit', 'QuantityUnitPrecision'])
            );

            if ($violations->count()) {
                /** @var ConstraintViolation $violation */
                $violation = $violations->getIterator()->current();
                $quickAddRow->addError($violation->getMessageTemplate(), $violation->getParameters());
            } else {
                $quickAddRow->setValid(true);
            }
        }
    }
}

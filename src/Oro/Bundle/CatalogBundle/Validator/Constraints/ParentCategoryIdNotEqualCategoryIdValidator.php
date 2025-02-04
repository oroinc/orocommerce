<?php

namespace Oro\Bundle\CatalogBundle\Validator\Constraints;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator for check that parent id is not equal category id
 */
class ParentCategoryIdNotEqualCategoryIdValidator extends ConstraintValidator
{
    /**
     * @param Category $value
     * @param Constraint $constraint
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $id = $value->getId();
        $parentId = $value->getParentCategory()?->getId();

        if (null === $id || null === $parentId) {
            return;
        }

        if ($id === $parentId) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('parentCategory')
                ->addViolation();
        }
    }
}

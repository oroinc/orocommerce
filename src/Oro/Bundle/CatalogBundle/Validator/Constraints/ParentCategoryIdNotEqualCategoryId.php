<?php

namespace Oro\Bundle\CatalogBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validator constraint for check that parent id is not equal category id
 */
class ParentCategoryIdNotEqualCategoryId extends Constraint
{
    public string $message = 'oro.catalog.category.parent_category.parent_category_id_same_as_id';

    public function getTargets(): string
    {
        return parent::CLASS_CONSTRAINT;
    }
}

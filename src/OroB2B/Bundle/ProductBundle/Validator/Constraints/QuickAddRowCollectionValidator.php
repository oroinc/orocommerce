<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\QuickAddRowCollection as QuickAddRowCollectionConstraint;

class QuickAddRowCollectionValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_product_quick_add_row_collection_validator';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param QuickAddRowCollection|QuickAddRow[] $value
     * @param Constraint|QuickAddRowCollectionConstraint $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $repository = $this->registry->getRepository('OroB2BProductBundle:Product');
        foreach ($value as $quickAddRow) {
            if (!$quickAddRow->isComplete()) {
                continue;
            }

            $valid = (bool) $repository->findOneBySku($quickAddRow->getSku());
            $quickAddRow->setValid($valid);
        }
    }
}

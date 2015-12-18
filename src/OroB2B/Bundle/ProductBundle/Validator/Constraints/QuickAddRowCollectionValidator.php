<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection as QuickAddRowCollectionModel;

class QuickAddRowCollectionValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_product_quick_add_row_collection';

    /**
     * @var EntityRepository|ProductRepository
     */
    protected $productRepository;

    /**
     * @param EntityRepository $productRepository
     */
    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param QuickAddRowCollectionModel|QuickAddRow[]|null $value
     * @param QuickAddRowCollection|Constraint $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value) {
            return;
        }

        foreach ($value as $quickAddRow) {
            if (!$quickAddRow->isComplete()) {
                continue;
            }

            $valid = (bool) $this->productRepository->findOneBySku($quickAddRow->getSku());
            $quickAddRow->setValid($valid);
        }
    }
}

<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProductBySkuValidator extends ConstraintValidator
{
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
     * @param string $value
     * @param Constraint|ProductBySku $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            $products = $this->registry->getRepository('OroB2BProductBundle:Product')->findOneBySku($value);
            if (empty($products)) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}

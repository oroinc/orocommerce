<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\ORM\PersistentCollection;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class PriceForProductUnitExistsValidator extends ConstraintValidator
{
    const PRICE_LIST_KEY = 'priceList';

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PersistentCollection $value The value that should be validated
     * @param PriceForProductUnitExists|Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof PersistentCollection) {
            return;
        }

        $product = $value->getOwner();
        $deletedUnits = [];
        $deletedUnitCodes = [];
        /** @var ProductUnitPrecision $precision */
        foreach ($value->getDeleteDiff() as $precision) {
            $deletedUnits[] = $precision->getUnit();
            $deletedUnitCodes[] = $precision->getUnit()->getCode();
        }

        if ($deletedUnits) {
            $repository = $this->registry
                ->getManagerForClass(PriceAttributeProductPrice::class)
                ->getRepository(PriceAttributeProductPrice::class);

            $pricesWithDeletedProductUnits = $repository->findBy(['product' => $product, 'unit' => $deletedUnits]);

            if ($pricesWithDeletedProductUnits) {
                $this->context->addViolation($constraint->message, ['%units%' => implode(', ', $deletedUnitCodes)]);
            }
        }
    }
}

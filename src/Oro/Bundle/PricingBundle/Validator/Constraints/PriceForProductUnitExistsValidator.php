<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PriceForProductUnitExistsValidator extends ConstraintValidator
{
    const PRICE_LIST_KEY = 'priceList';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
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
        /** @var FormInterface $form */
        $form = $this->context->getRoot();

        if (!$form || !$form instanceof FormInterface) {
            return;
        }

        $productPriceAttributeData = [];
        if ($form->has('productPriceAttributesPrices')) {
            $productPriceAttributeData = $form->get('productPriceAttributesPrices')->getData();
        }

        /** @var ProductUnitPrecision $precision */
        foreach ($value->getDeleteDiff() as $precision) {
            if (!$this->isEmptyProductPrice($precision, $productPriceAttributeData)) {
                $deletedUnits[] = $precision->getUnit();
                $deletedUnitCodes[] = $precision->getUnit()->getCode();
            }
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

    private function isEmptyProductPrice(ProductUnitPrecision $productUnitPrecision, array $productPriceAttributeData)
    {
        foreach ($productPriceAttributeData as $productPriceAttributes) {
            /** @var PriceAttributeProductPrice $productPriceAttribute */
            foreach ($productPriceAttributes as $productPriceAttribute) {
                if ($productPriceAttribute->getUnit()->getCode() === $productUnitPrecision->getUnit()->getCode() &&
                    $productPriceAttribute->getPrice()->getValue() !== null
                ) {
                    return false;
                }
            }
        }

        return true;
    }
}

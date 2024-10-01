<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates that {@see ProductKitItemProduct} has prices for {@see Product}.
 */
class ProductKitItemProductHasPriceValidator extends ConstraintValidator
{
    private FrontendProductPricesDataProvider $frontendProductPricesDataProvider;

    public function __construct(FrontendProductPricesDataProvider $frontendProductPricesDataProvider)
    {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
    }

    /**
     * @param Product $value
     * @param Constraint|ProductKitItemProductHasPrice $constraint
     *
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ProductKitItemProductHasPrice) {
            throw new UnexpectedTypeException($constraint, ProductKitItemProductHasPrice::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof ProductKitItemProduct) {
            throw new UnexpectedValueException($value, ProductKitItemProduct::class);
        }

        $product = $value->getProduct();

        $prices = $this->frontendProductPricesDataProvider
            ->getAllPricesForProducts([$product]);

        $productPrice = $prices[$product->getId()][$value->getKitItem()->getProductUnit()?->getCode()] ?? null;
        if ($productPrice === null) {
            $this->context
                ->buildViolation($constraint->productHasNoPriceMessage)
                ->atPath('product')
                ->addViolation();
        }
    }
}

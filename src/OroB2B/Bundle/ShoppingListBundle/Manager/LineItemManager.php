<?php
namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;

class LineItemManager
{
    /**
     * @var RoundingService
     */
    protected $roundingService;

    /**
     * @param RoundingService $roundingService
     */
    public function __construct(RoundingService $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /**
     * @param Product   $product
     * @param string    $unitCode
     * @param float|int $quantity
     *
     * @return float|int Rounded quantity
     */
    public function roundProductQuantity(Product $product, $unitCode, $quantity)
    {
        $unitPrecision = $product->getUnitPrecision($unitCode);
        if ($unitPrecision) {
            $quantity = $this->roundingService->round($quantity, $unitPrecision->getPrecision());
        }

        return $quantity;
    }
}

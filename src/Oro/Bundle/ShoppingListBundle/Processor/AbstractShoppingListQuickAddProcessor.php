<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Provides common functionality to handle logic related to quick order process.
 */
abstract class AbstractShoppingListQuickAddProcessor implements ComponentProcessorInterface
{
    protected ShoppingListLineItemHandler $shoppingListLineItemHandler;
    protected ManagerRegistry $doctrine;
    protected AclHelper $aclHelper;

    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        ManagerRegistry $doctrine,
        AclHelper $aclHelper
    ) {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    protected function fillShoppingList(ShoppingList $shoppingList, array $data): int
    {
        $data = $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY];
        $productSkus = array_column($data, ProductDataStorage::PRODUCT_SKU_KEY);

        $qb = $this->doctrine->getRepository(Product::class)->getProductsIdsBySkuQueryBuilder($productSkus);
        $productsData = $this->aclHelper->apply($qb)->getArrayResult();

        $productIds = [];
        foreach ($productsData as $key => $productData) {
            $productIds[$productData['sku']] = $productData['id'];
            unset($productsData[$key]);
        }

        $productUnitsWithQuantities = [];
        foreach ($data as $product) {
            $productQuantity = $product[ProductDataStorage::PRODUCT_QUANTITY_KEY];

            if (!isset($product[ProductDataStorage::PRODUCT_UNIT_KEY])) {
                continue;
            }

            $productUnit = $product[ProductDataStorage::PRODUCT_UNIT_KEY];

            $skuUppercase = mb_strtoupper($product[ProductDataStorage::PRODUCT_SKU_KEY]);
            if (\array_key_exists($skuUppercase, $productUnitsWithQuantities)
                && isset($productUnitsWithQuantities[$skuUppercase][$productUnit])
            ) {
                $productQuantity += $productUnitsWithQuantities[$skuUppercase][$productUnit];
            }

            $productUnitsWithQuantities[$skuUppercase][$productUnit] = $productQuantity;
        }

        try {
            return $this->shoppingListLineItemHandler->createForShoppingList(
                $shoppingList,
                array_values($productIds),
                $productUnitsWithQuantities
            );
        } catch (AccessDeniedException $e) {
            return 0;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isValidationRequired(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowed(): bool
    {
        return $this->shoppingListLineItemHandler->isAllowed();
    }
}

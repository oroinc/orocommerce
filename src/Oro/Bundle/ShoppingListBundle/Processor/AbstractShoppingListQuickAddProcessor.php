<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Base common logic for realization of ComponentProcessorInterface
 * provides defaults for:
 *  isValidationRequired(): true
 *  isAllowed(): ShoppingListLineItemHandler::isAllowed()
 *
 * provides base logic for fillShoppingList(ShoppingList $shoppingList, array $data)
 */
abstract class AbstractShoppingListQuickAddProcessor implements ComponentProcessorInterface
{
    /**
     * @var ShoppingListLineItemHandler
     */
    protected $shoppingListLineItemHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @var MessageGenerator
     */
    protected $messageGenerator;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        ManagerRegistry $registry,
        MessageGenerator $messageGenerator,
        AclHelper $aclHelper
    ) {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->registry = $registry;
        $this->messageGenerator = $messageGenerator;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param array $data
     * @return bool|int
     */
    protected function fillShoppingList(ShoppingList $shoppingList, array $data)
    {
        $data = $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY];
        $productSkus = \array_column($data, ProductDataStorage::PRODUCT_SKU_KEY);

        $qb = $this->getProductRepository()->getProductsIdsBySkuQueryBuilder($productSkus);
        $productsData = $this->aclHelper->apply($qb)->getArrayResult();

        $productIds = [];
        foreach ($productsData as $key => $productData) {
            $productIds[$productData['sku']] = $productData['id'];
            unset($productsData[$key]);
        }

        $productUnitsWithQuantities = [];
        foreach ($data as $product) {
            $productQuantity = $product['productQuantity'];

            if (!isset($product['productUnit'])) {
                continue;
            }

            $productUnit = $product['productUnit'];

            $upperSku = mb_strtoupper($product['productSku']);
            if (array_key_exists($upperSku, $productUnitsWithQuantities)) {
                if (isset($productUnitsWithQuantities[$upperSku][$productUnit])) {
                    $productQuantity += $productUnitsWithQuantities[$upperSku][$productUnit];
                }
            }

            $productUnitsWithQuantities[$upperSku][$productUnit] = $productQuantity;
        }

        try {
            $entitiesCount = $this->shoppingListLineItemHandler->createForShoppingList(
                $shoppingList,
                array_values($productIds),
                $productUnitsWithQuantities
            );

            return $entitiesCount;
        } catch (AccessDeniedException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isValidationRequired()
    {
        return true;
    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->registry->getManagerForClass($this->productClass)->getRepository($this->productClass);
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return $this->shoppingListLineItemHandler->isAllowed();
    }
}

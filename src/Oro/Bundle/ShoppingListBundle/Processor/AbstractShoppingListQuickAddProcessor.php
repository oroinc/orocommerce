<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;

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
     * @param ShoppingListLineItemHandler $shoppingListLineItemHandler
     * @param ManagerRegistry $registry,
     * @param MessageGenerator $messageGenerator
     */
    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        ManagerRegistry $registry,
        MessageGenerator $messageGenerator
    ) {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->registry = $registry;
        $this->messageGenerator = $messageGenerator;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param array $data
     * @return bool|int
     */
    protected function fillShoppingList(ShoppingList $shoppingList, array $data)
    {
        $data = $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY];
        $productSkus = ArrayUtil::arrayColumn($data, ProductDataStorage::PRODUCT_SKU_KEY);
        $productIds = $this->getProductRepository()->getProductsIdsBySku($productSkus);

        $productSkuQuantities = [];
        foreach ($data as $product) {
            $productQuantity = $product['productQuantity'];
            $upperSku = strtoupper($product['productSku']);
            if (array_key_exists($upperSku, $productSkuQuantities)) {
                $productQuantity += $productSkuQuantities[$upperSku];
            }
            $productSkuQuantities[$upperSku] = $productQuantity;
        }

        $productIdsQuantities = [];
        foreach ($productIds as $productSku => $productId) {
            $productIdsQuantities[$productId] = $productSkuQuantities[strtoupper($productSku)];
        }

        try {
            $entitiesCount = $this->shoppingListLineItemHandler->createForShoppingList(
                $shoppingList,
                array_values($productIds),
                $productIdsQuantities
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

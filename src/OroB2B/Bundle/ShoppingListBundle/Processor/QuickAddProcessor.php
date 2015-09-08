<?php

namespace OroB2B\Bundle\ShoppingListBundle\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UIBundle\Tools\ArrayUtils;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;
use OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;

class QuickAddProcessor implements ComponentProcessorInterface
{
    const NAME = 'orob2b_shopping_list_quick_add_processor';

    /** @var ShoppingListLineItemHandler */
    protected $shoppingListLineItemHandler;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $productClass;

    /**
     * @param ShoppingListLineItemHandler $shoppingListLineItemHandler
     * @param ManagerRegistry $registry
     */
    public function __construct(ShoppingListLineItemHandler $shoppingListLineItemHandler, ManagerRegistry $registry)
    {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->registry = $registry;
    }

    /** {@inheritdoc} */
    public function process(array $data, Request $request)
    {
        if (!$data) {
            return;
        }

        $shoppingListId = (int)$request->get('additional');
        $shoppingList = $this->shoppingListLineItemHandler->getShoppingList($shoppingListId);

        $productSkus = ArrayUtils::arrayColumn($data, 'productSku');
        $productIds = $this->getProductRepository()->getProductsIdsBySku($productSkus);
        $productQuantities = array_combine($productIds, ArrayUtils::arrayColumn($data, 'productQuantity'));

        $this->shoppingListLineItemHandler->createForShoppingList($shoppingList, $productIds, $productQuantities);
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }

    /** {@inheritdoc} */
    public function isValidationRequired()
    {
        return true;
    }

    /** @return ProductRepository */
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
}

<?php

namespace Oro\Bundle\ShoppingListBundle\Action;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\LineItem\Factory\LineItemByShoppingListAndProductFactoryInterface;

class AddConfigurableProductToShoppingListAction implements ShoppingListAndProductActionInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var LineItemByShoppingListAndProductFactoryInterface
     */
    private $lineItemFactory;

    /**
     * @param ConfigManager                                    $configManager
     * @param DoctrineHelper                                   $doctrineHelper
     * @param LineItemByShoppingListAndProductFactoryInterface $lineItemFactory
     */
    public function __construct(
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        LineItemByShoppingListAndProductFactoryInterface $lineItemFactory
    ) {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->lineItemFactory = $lineItemFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ShoppingList $shoppingList, Product $product)
    {
        if (!$this->isConfigurableProductAllowedInShoppingList()) {
            return;
        }

        if ($this->isShoppingListHasProductVariants($shoppingList, $product)) {
            return;
        }

        if ($this->isShoppingListHasConfigurableProduct($shoppingList, $product)) {
            return;
        }

        $this->addConfigurableProductToShoppingList($shoppingList, $product);
    }

    /**
     * @return bool
     */
    private function isConfigurableProductAllowedInShoppingList(): bool
    {
        return $this->configManager->get('oro_product.matrix_form_allow_empty');
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product      $product
     *
     * @return bool
     */
    private function isShoppingListHasProductVariants(ShoppingList $shoppingList, Product $product): bool
    {
        return $this->doctrineHelper->getEntityRepository(LineItem::class)->findOneBy([
            'shoppingList' => $shoppingList,
            'unit' => $product->getPrimaryUnitPrecision()->getUnit(),
            'product' => $product->getVariantProducts(),
        ]) !== null;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product      $product
     *
     * @return bool
     */
    private function isShoppingListHasConfigurableProduct(ShoppingList $shoppingList, Product $product): bool
    {
        return $this->doctrineHelper->getEntityRepository(LineItem::class)->findOneBy([
            'shoppingList' => $shoppingList,
            'unit' => $product->getPrimaryUnitPrecision()->getUnit(),
            'product' => $product,
        ]) !== null;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product      $product
     */
    private function addConfigurableProductToShoppingList(ShoppingList $shoppingList, Product $product)
    {
        $entityManager = $this->doctrineHelper->getEntityManagerForClass(LineItem::class);

        $entityManager->persist($this->lineItemFactory->create($shoppingList, $product));
        $entityManager->flush();
    }
}

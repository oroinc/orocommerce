<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductUnitsQuantityDataProvider implements DataProviderInterface
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var string
     */
    protected $shoppingListClassName;

    /**
     * @var string
     */
    protected $lineItemClassName;

    /**
     * @param SecurityFacade $securityFacade
     * @param RegistryInterface $registry
     * @param string $shoppingListClassName
     * @param string $lineItemClassName
     */
    public function __construct(
        SecurityFacade $securityFacade,
        RegistryInterface $registry,
        $shoppingListClassName,
        $lineItemClassName
    ) {
        $this->securityFacade = $securityFacade;
        $this->registry = $registry;
        $this->shoppingListClassName = $shoppingListClassName;
        $this->lineItemClassName = $lineItemClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $product = $context->data()->get('product');
        if (!$product) {
            return null;
        }

        $shoppingList = $this->getCurrentShoppingList();
        if (!$shoppingList) {
            return null;
        }

        return $this->getProductUnitsQuantity($shoppingList, $product);
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @return array
     */
    protected function getProductUnitsQuantity(ShoppingList $shoppingList, Product $product)
    {
        /* @var LineItemRepository $repository */
        $repository = $this->registry->getRepository($this->lineItemClassName);

        $items = $repository->getItemsByShoppingListAndProduct($shoppingList, $product);
        $units = [];

        foreach ($items as $item) {
            $units[$item->getProductUnitCode()] = $item->getQuantity();
        }

        return $units;
    }

    /**
     * @return null|ShoppingList
     */
    protected function getCurrentShoppingList()
    {
        $shoppingList = null;

        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof AccountUser) {
            /* @var ShoppingListRepository $repository */
            $repository = $this->registry->getRepository($this->shoppingListClassName);

            $shoppingList = $repository->findAvailableForAccountUser($user);
        }

        return $shoppingList;
    }
}

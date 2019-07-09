<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handles logic related to shopping list and line item manipulations (create, remove, etc.).
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ShoppingListManager
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var TranslatorInterface */
    private $translator;

    /** @var QuantityRoundingService */
    private $rounding;

    /** @var WebsiteManager */
    private $websiteManager;

    /** @var ShoppingListTotalManager */
    private $totalManager;

    /** @var ProductVariantAvailabilityProvider */
    private $productVariantProvider;

    /** @var ProductMatrixAvailabilityProvider */
    private $productMatrixAvailabilityProvider;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ManagerRegistry                    $doctrine
     * @param TokenAccessorInterface             $tokenAccessor
     * @param TranslatorInterface                $translator
     * @param QuantityRoundingService            $rounding
     * @param WebsiteManager                     $websiteManager
     * @param ShoppingListTotalManager           $totalManager
     * @param ProductVariantAvailabilityProvider $productVariantProvider
     * @param ProductMatrixAvailabilityProvider  $productMatrixAvailabilityProvider
     * @param ConfigManager                      $configManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        TokenAccessorInterface $tokenAccessor,
        TranslatorInterface $translator,
        QuantityRoundingService $rounding,
        WebsiteManager $websiteManager,
        ShoppingListTotalManager $totalManager,
        ProductVariantAvailabilityProvider $productVariantProvider,
        ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider,
        ConfigManager $configManager
    ) {
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->websiteManager = $websiteManager;
        $this->totalManager = $totalManager;
        $this->productVariantProvider = $productVariantProvider;
        $this->productMatrixAvailabilityProvider = $productMatrixAvailabilityProvider;
        $this->configManager = $configManager;
    }

    /**
     * Creates new shopping list
     *
     * @param string $label
     * @param bool   $flush
     *
     * @return ShoppingList
     */
    public function create($flush = false, $label = '')
    {
        $customerUser = $this->getCustomerUser();
        if (null === $customerUser) {
            throw new \LogicException('The customer user does not exist in the security context.');
        }

        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOrganization($customerUser->getOrganization())
            ->setCustomer($customerUser->getCustomer())
            ->setCustomerUser($customerUser)
            ->setWebsite($this->websiteManager->getCurrentWebsite());

        $shoppingList->setLabel($label !== '' ? $label : $this->translator->trans('oro.shoppinglist.default.label'));

        if ($flush) {
            $em = $this->getEntityManager();
            $em->persist($shoppingList);
            $em->flush($shoppingList);
        }

        return $shoppingList;
    }

    /**
     * @param LineItem     $lineItem
     * @param ShoppingList $shoppingList
     * @param bool         $flush
     * @param bool         $concatNotes
     */
    public function addLineItem(LineItem $lineItem, ShoppingList $shoppingList, $flush = true, $concatNotes = false)
    {
        $func = function (LineItem $duplicate) use ($lineItem, $concatNotes) {
            $this->mergeLineItems($lineItem, $duplicate, $concatNotes);
        };

        $this
            ->prepareLineItem($lineItem, $shoppingList)
            ->handleLineItem($lineItem, $shoppingList, $func);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param LineItem     $lineItem
     * @param ShoppingList $shoppingList
     */
    public function updateLineItem(LineItem $lineItem, ShoppingList $shoppingList)
    {
        $func = function (LineItem $duplicate) use ($lineItem) {
            if ($lineItem->getQuantity() > 0) {
                $this->updateLineItemQuantity($lineItem, $duplicate);
            } else {
                $this->removeLineItem($duplicate, true);
            }
        };

        $this
            ->prepareLineItem($lineItem, $shoppingList)
            ->handleLineItem($lineItem, $shoppingList, $func);

        $this->getEntityManager()->flush();
    }

    /**
     * @param int          $lineItemId
     * @param ShoppingList $shoppingList
     *
     * @return LineItem|null
     */
    public function getLineItem(int $lineItemId, ShoppingList $shoppingList): ?LineItem
    {
        $lineItems = $shoppingList->getLineItems();
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getId() === $lineItemId) {
                return $lineItem;
            }
        }

        return null;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product      $product
     * @param bool         $flush
     *
     * @return int Number of removed line items
     */
    public function removeProduct(ShoppingList $shoppingList, Product $product, $flush = true)
    {
        $em = $this->getEntityManager();

        $products = [];
        if ($product->isConfigurable()) {
            $products = $this->productVariantProvider->getSimpleProductsByVariantFields($product);
        }
        $products[] = $product;

        $lineItems = $this->getLineItemRepository($em)->getItemsByShoppingListAndProducts($shoppingList, $products);
        foreach ($lineItems as $lineItem) {
            $shoppingList->removeLineItem($lineItem);
            $em->remove($lineItem);
        }

        $this->totalManager->recalculateTotals($shoppingList, false);
        if ($lineItems && $flush) {
            $em->flush();
        }

        return count($lineItems);
    }

    /**
     * Removes the given line item. In case if line item is the part of matrix representation - removes all
     * line items of the product from the given line item.
     *
     * @param LineItem $lineItem
     * @param bool     $removeOnlyCurrentItem
     *
     * @return int Number of deleted line items
     */
    public function removeLineItem(LineItem $lineItem, bool $removeOnlyCurrentItem = false): int
    {
        $parentProduct = $lineItem->getParentProduct();
        if ($removeOnlyCurrentItem
            || !$parentProduct
            || $this->getAvailableMatrixFormType($parentProduct, $lineItem) === Configuration::MATRIX_FORM_NONE
        ) {
            $shoppingList = $lineItem->getShoppingList();
            $shoppingList->removeLineItem($lineItem);
            $em = $this->getEntityManager();
            $em->remove($lineItem);
            $this->totalManager->recalculateTotals($lineItem->getShoppingList(), false);
            $em->flush();

            // return 1 because only the specified line item was deleted
            return 1;
        }

        return $this->removeProduct(
            $lineItem->getShoppingList(),
            $parentProduct ? $parentProduct : $lineItem->getProduct()
        );
    }

    /**
     * @param array        $lineItems
     * @param ShoppingList $shoppingList
     * @param int          $batchSize
     *
     * @return int
     */
    public function bulkAddLineItems(array $lineItems, ShoppingList $shoppingList, $batchSize)
    {
        $lineItemsCount = count($lineItems);
        for ($iteration = 1; $iteration <= $lineItemsCount; $iteration++) {
            $flush = $iteration % $batchSize === 0 || $lineItemsCount === $iteration;
            $this->addLineItem($lineItems[$iteration - 1], $shoppingList, $flush);
        }

        return $lineItemsCount;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string       $label
     *
     * @return ShoppingList
     */
    public function edit($shoppingList, $label = '')
    {
        $customerUser = $this->getCustomerUser();
        if (null !== $customerUser) {
            $shoppingList
                ->setOrganization($customerUser->getOrganization())
                ->setCustomer($customerUser->getCustomer())
                ->setCustomerUser($customerUser)
                ->setWebsite($this->websiteManager->getCurrentWebsite());
            if ('' !== $label) {
                $shoppingList->setLabel($label);
            }
        }

        return $shoppingList;
    }

    /**
     * @param ShoppingList $shoppingList
     */
    public function removeLineItems($shoppingList)
    {
        $em = $this->getEntityManager();
        $lineItems = $shoppingList->getLineItems();
        foreach ($lineItems as $lineItem) {
            $shoppingList->removeLineItem($lineItem);
            $em->remove($lineItem);
        }
        $this->totalManager->recalculateTotals($shoppingList, false);
        $em->flush();
    }

    /**
     * @param LineItem $lineItem
     * @param LineItem $duplicate
     * @param bool     $concatNotes
     */
    private function mergeLineItems(LineItem $lineItem, LineItem $duplicate, $concatNotes)
    {
        $quantity = $this->rounding->roundQuantity(
            $duplicate->getQuantity() + $lineItem->getQuantity(),
            $duplicate->getUnit(),
            $duplicate->getProduct()
        );
        $duplicate->setQuantity($quantity);

        if ($concatNotes) {
            $notes = trim(implode(' ', [$duplicate->getNotes(), $lineItem->getNotes()]));
            $duplicate->setNotes($notes);
        }
    }

    /**
     * Set new quantity for $duplicate line item based on quantity value from $lineItem
     *
     * @param LineItem $lineItem
     * @param LineItem $duplicate
     */
    private function updateLineItemQuantity(LineItem $lineItem, LineItem $duplicate)
    {
        $quantity = $this->rounding->roundQuantity(
            $lineItem->getQuantity(),
            $duplicate->getUnit(),
            $duplicate->getProduct()
        );
        $duplicate->setQuantity($quantity);
    }

    /**
     * @param LineItem     $lineItem
     * @param ShoppingList $shoppingList
     *
     * @return ShoppingListManager
     */
    private function prepareLineItem(LineItem $lineItem, ShoppingList $shoppingList)
    {
        $this->ensureProductTypeAllowed($lineItem);

        $lineItem->setShoppingList($shoppingList);
        if (null === $lineItem->getCustomerUser() && $shoppingList->getCustomerUser()) {
            $lineItem->setCustomerUser($shoppingList->getCustomerUser());
        }
        if (null === $lineItem->getOrganization() && $shoppingList->getOrganization()) {
            $lineItem->setOrganization($shoppingList->getOrganization());
        }

        return $this;
    }

    /**
     * @param LineItem     $lineItem
     * @param ShoppingList $shoppingList
     * @param \Closure     $func
     *
     * @return ShoppingListManager
     */
    private function handleLineItem(LineItem $lineItem, ShoppingList $shoppingList, \Closure $func)
    {
        $em = $this->getEntityManager();
        $duplicate = $this->getLineItemRepository($em)->findDuplicate($lineItem);
        if ($duplicate instanceof LineItem && $shoppingList->getId()) {
            $func($duplicate);
            $em->remove($lineItem);
        } elseif ($lineItem->getQuantity() > 0) {
            $shoppingList->addLineItem($lineItem);
            $em->persist($lineItem);
        }

        $this->totalManager->recalculateTotals($shoppingList, false);

        return $this;
    }

    /**
     * @param LineItem $lineItem
     *
     * @throws \InvalidArgumentException
     */
    private function ensureProductTypeAllowed(LineItem $lineItem)
    {
        $product = $lineItem->getProduct();

        if ($product && !$product->isSimple()) {
            throw new \InvalidArgumentException('Can not save not simple product');
        }
    }

    /**
     * @return CustomerUser|null
     */
    private function getCustomerUser()
    {
        $user = $this->tokenAccessor->getUser();

        return $user instanceof CustomerUser
            ? $user
            : null;
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrine->getManagerForClass(ShoppingList::class);
    }

    /**
     * @return LineItemRepository
     */
    private function getLineItemRepository(EntityManagerInterface $em)
    {
        return $em->getRepository(LineItem::class);
    }

    /**
     * @param Product $product
     * @param LineItem $lineItem
     * @return string
     */
    private function getAvailableMatrixFormType(Product $product, LineItem $lineItem)
    {
        if ($product->getPrimaryUnitPrecision()->getProductUnitCode() !== $lineItem->getProductUnitCode()) {
            return Configuration::MATRIX_FORM_NONE;
        }

        $matrixConfiguration = $this->configManager->get(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_SHOPPING_LIST)
        );

        if ($matrixConfiguration === Configuration::MATRIX_FORM_NONE
            || !$this->productMatrixAvailabilityProvider->isMatrixFormAvailable($product)
        ) {
            return Configuration::MATRIX_FORM_NONE;
        }

        return $matrixConfiguration;
    }
}

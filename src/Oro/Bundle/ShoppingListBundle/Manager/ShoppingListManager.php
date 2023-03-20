<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\LineItemChecksumGeneratorInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to shopping list and line item manipulations (create, remove, etc.).
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ShoppingListManager
{
    private ManagerRegistry $doctrine;

    private TokenAccessorInterface $tokenAccessor;

    private TranslatorInterface $translator;

    private QuantityRoundingService $rounding;

    private WebsiteManager $websiteManager;

    private ShoppingListTotalManager $totalManager;

    private ProductVariantAvailabilityProvider $productVariantProvider;

    private ConfigManager $configManager;

    private EntityDeleteHandlerRegistry $deleteHandlerRegistry;

    private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ManagerRegistry $doctrine,
        TokenAccessorInterface $tokenAccessor,
        TranslatorInterface $translator,
        QuantityRoundingService $rounding,
        WebsiteManager $websiteManager,
        ShoppingListTotalManager $totalManager,
        ProductVariantAvailabilityProvider $productVariantProvider,
        ConfigManager $configManager,
        EntityDeleteHandlerRegistry $deleteHandlerRegistry,
        LineItemChecksumGeneratorInterface $lineItemChecksumGenerator
    ) {
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->websiteManager = $websiteManager;
        $this->totalManager = $totalManager;
        $this->productVariantProvider = $productVariantProvider;
        $this->configManager = $configManager;
        $this->deleteHandlerRegistry = $deleteHandlerRegistry;
        $this->lineItemChecksumGenerator = $lineItemChecksumGenerator;
    }

    /**
     * Creates new shopping list
     *
     * @param bool $flush
     * @param string $label
     * @param CustomerUser|null $customerUser
     * @return ShoppingList
     */
    public function create($flush = false, $label = '', CustomerUser $customerUser = null)
    {
        if (!$customerUser) {
            $customerUser = $this->getCustomerUser();
            if (!$customerUser) {
                throw new \LogicException('The customer user does not exist in the security context.');
            }
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

        $this->totalManager->recalculateTotals($shoppingList, false);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->persist($shoppingList);
            $entityManager->flush();
        }
    }

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

        $this->totalManager->recalculateTotals($shoppingList, false);
        $this->getEntityManager()->flush();
    }

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
        $products = [];
        if ($product->isConfigurable()) {
            $products = $this->productVariantProvider->getSimpleProductsByVariantFields($product);
        }
        $products[] = $product;

        $lineItems = $this->getLineItemRepository($this->getEntityManager())
            ->getItemsByShoppingListAndProducts($shoppingList, $products);
        $this->deleteLineItems($lineItems, $flush);

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
        $product = $lineItem->getProduct();
        if ($removeOnlyCurrentItem || (!$parentProduct && !$product->isKit())) {
            $this->deleteHandlerRegistry->getHandler(LineItem::class)->delete($lineItem);

            // return 1 because only the specified line item was deleted
            return 1;
        }

        return $this->removeProduct($lineItem->getShoppingList(), $parentProduct ?: $product);
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
            $lineItem = $lineItems[$iteration - 1];

            $this
                ->prepareLineItem($lineItem, $shoppingList)
                ->handleLineItem($lineItem, $shoppingList, function (LineItem $duplicate) use ($lineItem) {
                    $this->mergeLineItems($lineItem, $duplicate, false);
                });

            if ($lineItemsCount === $iteration) {
                // Recalculates totals on last iteration.
                $this->totalManager->recalculateTotals($shoppingList, false);
            }

            if ($iteration % $batchSize === 0 || $lineItemsCount === $iteration) {
                // Flushes entity manager once batch is ready or it the last iteration.
                $this->getEntityManager()->flush();
            }
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
        $this->deleteLineItems($shoppingList->getLineItems()->toArray());
    }

    /**
     * Removes shopping list line items containing products with unavailable inventory statuses.
     * Recalculates subtotals if line items were removed.
     */
    public function actualizeLineItems(ShoppingList $shoppingList)
    {
        /** @var LineItemRepository $repository */
        $repository = $this->doctrine
            ->getManagerForClass(LineItem::class)
            ->getRepository(LineItem::class);

        $allowedStatuses = $this->configManager->get('oro_product.general_frontend_product_visibility');
        if ($repository->deleteNotAllowedLineItemsFromShoppingList($shoppingList, $allowedStatuses)) {
            $this->totalManager->recalculateTotals($shoppingList, true);
        }
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
        if (null === $lineItem->getCustomerUser() && $shoppingList->getCustomerUser()) {
            $lineItem->setCustomerUser($shoppingList->getCustomerUser());
        }
        if (null === $lineItem->getOrganization() && $shoppingList->getOrganization()) {
            $lineItem->setOrganization($shoppingList->getOrganization());
        }

        $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
        if ($checksum !== null) {
            $lineItem->setChecksum($checksum);
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
        $duplicate = $this->getLineItemRepository($em)->findDuplicateInShoppingList($lineItem, $shoppingList);
        if ($duplicate) {
            $func($duplicate);
            $em->remove($lineItem);
        } elseif ($lineItem->getQuantity() > 0 || !$lineItem->getProduct()->isSimple()) {
            $shoppingList->addLineItem($lineItem);
            $em->persist($lineItem);
        }

        return $this;
    }

    /**
     * @param LineItem[] $lineItems
     * @param bool       $flush
     */
    private function deleteLineItems(array $lineItems, bool $flush = true)
    {
        if (!$lineItems) {
            return;
        }

        $handler = $this->deleteHandlerRegistry->getHandler(LineItem::class);
        $flushAllOptions = [];
        foreach ($lineItems as $lineItem) {
            $flushAllOptions[] = $handler->delete($lineItem, false);
        }
        if ($flush) {
            $handler->flushAll($flushAllOptions);
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
     * @param EntityManagerInterface $em
     *
     * @return LineItemRepository
     */
    private function getLineItemRepository(EntityManagerInterface $em)
    {
        return $em->getRepository(LineItem::class);
    }
}

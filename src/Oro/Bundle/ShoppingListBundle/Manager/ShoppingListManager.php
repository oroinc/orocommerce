<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to shopping list and line item manipulations (create, remove, etc.).
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListManager
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private ManagerRegistry $doctrine,
        private TokenAccessorInterface $tokenAccessor,
        private TranslatorInterface $translator,
        private QuantityRoundingService $rounding,
        private WebsiteManager $websiteManager,
        private ShoppingListTotalManager $totalManager,
        private ProductVariantAvailabilityProvider $productVariantProvider,
        private ConfigManager $configManager,
        private EntityDeleteHandlerRegistry $deleteHandlerRegistry,
        private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator
    ) {
    }

    public function create(bool $flush = false, string $label = '', ?CustomerUser $customerUser = null): ShoppingList
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

    public function addLineItem(
        LineItem $lineItem,
        ShoppingList $shoppingList,
        bool $flush = true,
        bool $concatNotes = false
    ): void {
        $func = function (LineItem $duplicate) use ($lineItem, $concatNotes) {
            $this->mergeLineItems($lineItem, $duplicate, $concatNotes);
        };

        $this->prepareLineItem($lineItem, $shoppingList);
        $this->handleLineItem($lineItem, $shoppingList, $func);

        $this->totalManager->invalidateAndRecalculateTotals($shoppingList, false);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->persist($shoppingList);
            $entityManager->flush();
        }
    }

    public function batchUpdateLineItems(
        array $batchItems,
        ShoppingList $shoppingList,
        $flush = true,
        $concatNotes = false,
    ): void {
        array_walk($batchItems, function ($lineItem) use ($shoppingList) {
            $this->prepareLineItem($lineItem, $shoppingList); // update checksum for items at first.
        });

        $entityManager = $this->getEntityManager();
        /** @var LineItem $lineItem */
        foreach ($batchItems as $lineItem) {
            $duplicate = $this->getLineItemRepository($entityManager)
                ->findDuplicateInShoppingList($lineItem, $shoppingList);
            if ($duplicate) {
                if (isset($batchItems[$duplicate->getId()]) && $lineItem->getChecksum() !== $duplicate->getChecksum()) {
                    // In case duplicated line item is also in updating batch, check it if no longer duplicated.
                    $entityManager->remove($duplicate);
                    $entityManager->flush($duplicate);
                } else {
                    $this->mergeLineItems($lineItem, $duplicate, $concatNotes);
                    $shoppingList->removeAssociatedListLineItem($lineItem);
                    $entityManager->remove($lineItem);
                    $lineItem->setQuantity(0);
                }
            }

            if ($lineItem->getQuantity() > 0 || !$lineItem->getProduct()->isSimple()) {
                $shoppingList->addAssociatedListLineItem($lineItem);
                $entityManager->persist($lineItem);
            }
        }

        $this->totalManager->invalidateAndRecalculateTotals($shoppingList, false);

        if ($flush) {
            $entityManager->persist($shoppingList);
            $entityManager->flush();
        }
    }

    public function updateLineItem(LineItem $lineItem, ShoppingList $shoppingList, bool $flush = true): void
    {
        $func = function (LineItem $duplicate) use ($lineItem) {
            if ($lineItem->getQuantity() > 0) {
                $this->updateLineItemQuantity($lineItem, $duplicate);
            } else {
                $this->removeLineItem($duplicate, true);
            }
        };

        $this->prepareLineItem($lineItem, $shoppingList);
        $this->handleLineItem($lineItem, $shoppingList, $func);

        $this->totalManager->invalidateAndRecalculateTotals($shoppingList, false);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
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
     * @return int Number of removed line items
     */
    public function removeProduct(ShoppingList $shoppingList, Product $product, bool $flush = true): int
    {
        $products = [];
        if ($product->isConfigurable()) {
            $products = $this->productVariantProvider->getSimpleProductsByVariantFields($product);
        }
        $products[] = $product;

        $lineItems = $this->getLineItemRepository($this->getEntityManager())
            ->getAllItemsByShoppingListAndProducts($shoppingList, $products);
        $this->deleteLineItems($lineItems, $flush);

        return \count($lineItems);
    }

    /**
     * Removes the given line item. In case if line item is the part of matrix representation - removes all
     * line items of the product from the given line item.
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

        return $this->removeProduct($lineItem->getAssociatedList(), $parentProduct ?: $product);
    }

    /**
     * @return int Number of line items
     */
    public function bulkAddLineItems(array $lineItems, ShoppingList $shoppingList, int $batchSize): int
    {
        $lineItemsCount = \count($lineItems);
        for ($iteration = 1; $iteration <= $lineItemsCount; $iteration++) {
            $lineItem = $lineItems[$iteration - 1];

            $this->prepareLineItem($lineItem, $shoppingList);
            $this->handleLineItem($lineItem, $shoppingList, function (LineItem $duplicate) use ($lineItem) {
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

    public function edit(ShoppingList $shoppingList, string $label = ''): ShoppingList
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

    public function removeLineItems(ShoppingList $shoppingList): void
    {
        $this->deleteLineItems($shoppingList->getLineItems()->toArray());
    }

    /**
     * Removes shopping list line items containing products with unavailable inventory statuses.
     * Recalculates subtotals if line items were removed.
     *
     * Return true if the shopping list was changed, false otherwise
     */
    public function actualizeLineItems(ShoppingList $shoppingList): bool
    {
        /** @var LineItemRepository $repository */
        $repository = $this->doctrine
            ->getManagerForClass(LineItem::class)
            ->getRepository(LineItem::class);

        $allowedStatuses = $this->configManager->get('oro_product.general_frontend_product_visibility');
        $count = $repository->deleteNotAllowedLineItemsFromShoppingList($shoppingList, $allowedStatuses);

        if ($count) {
            $this->totalManager->recalculateTotals($shoppingList, true);
        }

        return (bool)$count;
    }

    private function mergeLineItems(LineItem $lineItem, LineItem $duplicate, bool $concatNotes): void
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
    private function updateLineItemQuantity(LineItem $lineItem, LineItem $duplicate): void
    {
        $quantity = $lineItem->getQuantity();
        if ($lineItem->getProduct()?->isKit()) {
            $quantity += $duplicate->getQuantity();
        }
        $quantity = $this->rounding->roundQuantity(
            $quantity,
            $duplicate->getUnit(),
            $duplicate->getProduct()
        );
        $duplicate->setQuantity($quantity);
    }

    private function prepareLineItem(LineItem $lineItem, ShoppingList $shoppingList): void
    {
        if ($shoppingList->getCustomerUser() && $lineItem->getCustomerUser() !== $shoppingList->getCustomerUser()) {
            $lineItem->setCustomerUser($shoppingList->getCustomerUser());
        }
        if ($shoppingList->getOrganization() && $lineItem->getOrganization() !== $shoppingList->getOrganization()) {
            $lineItem->setOrganization($shoppingList->getOrganization());
        }

        $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
        if ($checksum !== null) {
            $lineItem->setChecksum($checksum);
        }
    }

    private function handleLineItem(LineItem $lineItem, ShoppingList $shoppingList, \Closure $func): void
    {
        $em = $this->getEntityManager();
        $duplicate = $this->getLineItemRepository($em)->findDuplicateInShoppingList($lineItem, $shoppingList);
        if ($duplicate) {
            $func($duplicate);
            $em->remove($lineItem);
            // Ensures that ShoppingList::$lineItems collection is up-to-date. Required, for example for correct
            // subtotal calculations.
            $shoppingList->removeAssociatedListLineItem($lineItem);
        } elseif ($lineItem->getQuantity() > 0 || !$lineItem->getProduct()->isSimple()) {
            $shoppingList->addAssociatedListLineItem($lineItem);
            $em->persist($lineItem);
        }
    }

    private function deleteLineItems(array $lineItems, bool $flush = true): void
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

    private function getCustomerUser(): ?CustomerUser
    {
        $user = $this->tokenAccessor->getUser();

        return $user instanceof CustomerUser
            ? $user
            : null;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(ShoppingList::class);
    }

    private function getLineItemRepository(EntityManagerInterface $em): LineItemRepository
    {
        return $em->getRepository(LineItem::class);
    }
}

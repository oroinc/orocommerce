<?php

namespace Oro\Bundle\CheckoutBundle\Api\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutLineItemGroup;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemGroupTitleProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;
use Oro\Component\EntitySerializer\EntityConfig;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * The repository to get checkout line item groups.
 */
class CheckoutLineItemGroupRepository
{
    public const string SHIPPING_TYPE_CHECKOUT = 'checkout';
    public const string SHIPPING_TYPE_LINE_ITEM = 'line_item';
    public const string SHIPPING_TYPE_LINE_ITEM_GROUP = 'line_item_group';

    private array $checkouts = [];
    private array $groupedLineItems = [];

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly QueryAclHelper $queryAclHelper,
        private readonly ConfigProvider $configProvider,
        private readonly GroupLineItemHelperInterface $groupLineItemHelper,
        private readonly LineItemGroupTitleProvider $titleProvider,
        private readonly CheckoutLineItemsManager $checkoutLineItemsManager,
        private readonly LineItemSubtotalProvider $lineItemsSubtotalProvider
    ) {
    }

    public function getShippingType(): string
    {
        if ($this->configProvider->isShippingSelectionByLineItemEnabled()) {
            return self::SHIPPING_TYPE_LINE_ITEM;
        }
        if ($this->configProvider->isLineItemsGroupingEnabled()) {
            return self::SHIPPING_TYPE_LINE_ITEM_GROUP;
        }

        return self::SHIPPING_TYPE_CHECKOUT;
    }

    public function findGroup(string $groupId): ?CheckoutLineItemGroup
    {
        if (!$this->configProvider->isLineItemsGroupingEnabled()) {
            return null;
        }

        $groupIdParts = $this->parseGroupId($groupId);
        if (null === $groupIdParts) {
            return null;
        }

        [$checkoutId, $lineItemGroupKey] = $groupIdParts;
        /** @var Checkout|null $checkout */
        $checkout = $this->getCheckout($checkoutId);
        if (null === $checkout) {
            return null;
        }

        $itemCount = 0;
        $name = null;
        $totalValue = 0.0;
        $currency = null;
        $shippingMethod = null;
        $shippingMethodType = null;
        $shippingEstimateAmount = null;
        $groupingFieldPath = $this->configProvider->getGroupLineItemsByField();
        $lineItems = $this->checkoutLineItemsManager->getData($checkout);
        foreach ($lineItems as $lineItem) {
            if ($this->groupLineItemHelper->getLineItemGroupKey($lineItem, $groupingFieldPath) === $lineItemGroupKey) {
                if (0 === $itemCount) {
                    $name = $this->getGroupTitle($lineItem, $lineItemGroupKey);
                    $currency = $lineItem->getCurrency();
                    $shippingData = $checkout->getLineItemGroupShippingData();
                    if (isset($shippingData[$lineItemGroupKey])) {
                        $shippingMethod = $shippingData[$lineItemGroupKey]['method'];
                        $shippingMethodType = $shippingData[$lineItemGroupKey]['type'];
                        $shippingEstimateAmount = $shippingData[$lineItemGroupKey]['amount'];
                    }
                }
                $itemCount++;
                $totalValue += $this->lineItemsSubtotalProvider->getRowTotal($lineItem, $currency);
            }
        }
        if (0 === $itemCount) {
            return null;
        }

        $group = new CheckoutLineItemGroup($groupId, $checkoutId, $lineItemGroupKey);
        $group->setName($name);
        $group->setItemCount($itemCount);
        $group->setTotalValue($totalValue);
        $group->setCurrency($currency);
        $group->setShippingMethod($shippingMethod);
        $group->setShippingMethodType($shippingMethodType);
        $group->setShippingEstimateAmount($shippingEstimateAmount);

        return $group;
    }

    /**
     * @return string[]
     */
    public function getGroupIds(int $checkoutId, RequestType $requestType): array
    {
        if (!$this->configProvider->isLineItemsGroupingEnabled()) {
            return [];
        }

        $result = [];
        $groupedLineItems = $this->getGroupedLineItems($checkoutId, $requestType);
        foreach ($groupedLineItems as $lineItemGroupKey => $lineItemsInGroup) {
            $result[] = $this->buildGroupId($checkoutId, $lineItemGroupKey);
        }

        return $result;
    }

    public function getGroupId(int $checkoutId, int $lineItemId, RequestType $requestType): ?string
    {
        if (!$this->configProvider->isLineItemsGroupingEnabled()) {
            return null;
        }

        $groupedLineItems = $this->getGroupedLineItems($checkoutId, $requestType);
        foreach ($groupedLineItems as $lineItemGroupKey => $lineItemsInGroup) {
            foreach ($lineItemsInGroup as $lineItem) {
                if ($lineItem->getId() === $lineItemId) {
                    return $this->buildGroupId($checkoutId, $lineItemGroupKey);
                }
            }
        }

        return null;
    }

    /**
     * @return array ['product.category:1' => [line item, ...], ...]
     */
    private function getGroupedLineItems(int $checkoutId, RequestType $requestType): array
    {
        $cacheKey = $checkoutId . ':' . $requestType;
        if (!\array_key_exists($cacheKey, $this->groupedLineItems)) {
            $qb = $this->doctrineHelper->createQueryBuilder(CheckoutLineItem::class, 'li')
                ->select('li, p')
                ->leftJoin('li.product', 'p')
                ->where('li.checkout = :checkoutId')
                ->setParameter('checkoutId', $checkoutId);
            $query = $this->queryAclHelper->protectQuery($qb, new EntityConfig(), $requestType);
            $query->setHint(Query::HINT_REFRESH, true);
            $lineItems = $query->getResult();
            if (!$lineItems) {
                return [];
            }
            $this->groupedLineItems[$cacheKey] = $this->groupLineItemHelper->getGroupedLineItems(
                new ArrayCollection($lineItems),
                $this->configProvider->getGroupLineItemsByField()
            );
        }

        return $this->groupedLineItems[$cacheKey];
    }

    private function getCheckout(int $checkoutId): ?Checkout
    {
        if (!\array_key_exists($checkoutId, $this->checkouts)) {
            $this->checkouts[$checkoutId] = $this->doctrineHelper->createQueryBuilder(Checkout::class, 'c')
                ->select('c, li, p')
                ->leftJoin('c.lineItems', 'li')
                ->leftJoin('li.product', 'p')
                ->where('c.id = :id')
                ->setParameter('id', $checkoutId)
                ->getQuery()
                ->setHint(Query::HINT_REFRESH, true)
                ->getOneOrNullResult();
        }

        return $this->checkouts[$checkoutId];
    }

    private function buildGroupId(int $checkoutId, string $lineItemGroupKey): string
    {
        return base64_encode(\sprintf('%d-%s', $checkoutId, $lineItemGroupKey));
    }

    /**
     * @return array|null [checkoutId, lineItemGroupKey]
     */
    private function parseGroupId(string $groupId): ?array
    {
        $decodedGroupId = base64_decode($groupId);
        if (!$decodedGroupId) {
            return null;
        }

        $parts = explode('-', $decodedGroupId, 2);
        if (\count($parts) !== 2) {
            return null;
        }

        $checkoutId = filter_var($parts[0], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if (null === $checkoutId) {
            return null;
        }
        $lineItemGroupKey = $parts[1];
        if (!$lineItemGroupKey) {
            return null;
        }

        return [$checkoutId, $lineItemGroupKey];
    }

    private function getGroupTitle(object $lineItem, string $lineItemGroupKey): ?string
    {
        try {
            return $this->titleProvider->getTitle($lineItemGroupKey, $lineItem);
        } catch (NoSuchPropertyException) {
            return null;
        }
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProviderInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Checks if line item groups shipping methods should be updated from stored in checkout attribute value.
 */
class IsLineItemGroupsShippingMethodsUpdateRequired extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private const ENTITY = 'entity';
    private const LINE_ITEM_GROUPS_SHIPPING_DATA = 'line_item_groups_shipping_data';

    private CheckoutLineItemsProvider $checkoutLineItemsProvider;
    private GroupedLineItemsProviderInterface $groupingService;
    private CheckoutFactoryInterface $checkoutFactory;
    private mixed $entity = null;
    private mixed $lineItemGroupsShippingData = null;

    public function __construct(
        CheckoutLineItemsProvider $checkoutLineItemsProvider,
        GroupedLineItemsProviderInterface $groupingService,
        CheckoutFactoryInterface $checkoutFactory
    ) {
        $this->checkoutLineItemsProvider = $checkoutLineItemsProvider;
        $this->groupingService = $groupingService;
        $this->checkoutFactory = $checkoutFactory;
    }

    /**
     * {@inheritDoc}
     * Line item group shipping methods should be updated if stored checkout value is not empty
     * but some line item group has no shipping method.
     */
    protected function isConditionAllowed($context)
    {
        $lineItemGroupsShippingData = $this->resolveValue($context, $this->lineItemGroupsShippingData);
        if (empty($lineItemGroupsShippingData)) {
            return false;
        }

        $entity = $this->resolveValue($context, $this->entity);
        if ($entity instanceof Checkout) {
            $groupedLineItems = $this->getGroupedLineItems($entity);
            foreach ($groupedLineItems as $lineItemGroupKey => $lineItems) {
                if (!isset($lineItemGroupShippingData[$lineItemGroupKey]['method'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'is_line_item_groups_shipping_methods_update_required';
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (\array_key_exists(self::ENTITY, $options)) {
            $this->entity = $options[self::ENTITY];
        }
        if (\array_key_exists(0, $options)) {
            $this->entity = $options[0];
        }
        if (!$this->entity) {
            throw new InvalidArgumentException(sprintf('Missing "%s" option', self::ENTITY));
        }

        if (\array_key_exists(self::LINE_ITEM_GROUPS_SHIPPING_DATA, $options)) {
            $this->lineItemGroupsShippingData = $options[self::LINE_ITEM_GROUPS_SHIPPING_DATA];
        }
        if (\array_key_exists(1, $options)) {
            $this->lineItemGroupsShippingData = $options[1];
        }
        if (null === $this->lineItemGroupsShippingData) {
            throw new InvalidArgumentException(sprintf('Missing "%s" option', self::LINE_ITEM_GROUPS_SHIPPING_DATA));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->entity, $this->lineItemGroupsShippingData]);
    }

    /**
     * {@inheritDoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->entity, $this->lineItemGroupsShippingData], $factoryAccessor);
    }

    private function getGroupedLineItems(Checkout $checkout): array
    {
        return $this->groupingService->getGroupedLineItems(
            $this->checkoutFactory->createCheckout(
                $checkout,
                $this->checkoutLineItemsProvider->getCheckoutLineItems($checkout)
            )
        );
    }
}

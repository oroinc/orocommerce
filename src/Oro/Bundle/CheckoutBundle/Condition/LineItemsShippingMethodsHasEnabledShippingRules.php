<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Checks if line items shipping methods has available shipping rules.
 * Usage:
 * @line_items_shipping_methods_has_enabled_shipping_rules: $checkout
 */
class LineItemsShippingMethodsHasEnabledShippingRules extends AbstractCondition implements
    ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private const OPTION_ENTITY = 'entity';

    private ManagerRegistry $doctrine;
    private CheckoutLineItemsProvider $checkoutLineItemsProvider;
    private mixed $entity = null;

    public function __construct(
        ManagerRegistry $doctrine,
        CheckoutLineItemsProvider $checkoutLineItemsProvider
    ) {
        $this->doctrine = $doctrine;
        $this->checkoutLineItemsProvider = $checkoutLineItemsProvider;
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        $valid = true;
        $entity = $this->resolveValue($context, $this->entity);
        if ($entity instanceof Checkout) {
            $checkedMethods = [];
            $repository = $this->doctrine->getRepository(ShippingMethodsConfigsRule::class);
            $lineItems = $this->checkoutLineItemsProvider->getCheckoutLineItems($entity);
            foreach ($lineItems as $lineItem) {
                $shippingMethod = $lineItem->getShippingMethod();
                if (!$shippingMethod) {
                    $valid = false;
                    break;
                }

                if (isset($checkedMethods[$shippingMethod])) {
                    continue;
                }

                if (!$repository->getEnabledRulesByMethod($shippingMethod)) {
                    $valid = false;
                    break;
                }

                $checkedMethods[$shippingMethod] = true;
            }
        }

        return $valid;
    }

    #[\Override]
    public function getName()
    {
        return 'line_items_shipping_methods_has_enabled_shipping_rules';
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (\array_key_exists(self::OPTION_ENTITY, $options)) {
            $this->entity = $options[self::OPTION_ENTITY];
        }
        if (\array_key_exists(0, $options)) {
            $this->entity = $options[0];
        }
        if (!$this->entity) {
            throw new InvalidArgumentException(sprintf('Missing "%s" option', self::OPTION_ENTITY));
        }

        return $this;
    }

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray([$this->entity]);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->entity], $factoryAccessor);
    }
}

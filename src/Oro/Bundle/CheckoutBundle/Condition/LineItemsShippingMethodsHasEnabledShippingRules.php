<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Workflow condition to check if line items shipping methods has available shipping rules.
 */
class LineItemsShippingMethodsHasEnabledShippingRules extends AbstractCondition implements
    ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private const OPTION_ENTITY = 'entity';
    private const CONDITION_NAME = 'line_items_shipping_methods_has_enabled_shipping_rules';

    /**
     * @var mixed
     */
    private $entity;
    private ShippingMethodsConfigsRuleRepository $repository;
    private CheckoutLineItemsProvider $checkoutLineItemsProvider;

    public function __construct(
        ShippingMethodsConfigsRuleRepository $repository,
        CheckoutLineItemsProvider $checkoutLineItemsProvider
    ) {
        $this->repository = $repository;
        $this->checkoutLineItemsProvider = $checkoutLineItemsProvider;
    }

    protected function isConditionAllowed($context)
    {
        $entity = $this->resolveValue($context, $this->entity);
        $valid = true;

        if ($entity instanceof Checkout) {
            $lineItems = $this->checkoutLineItemsProvider->getCheckoutLineItems($entity);

            $checkedMethods = [];

            foreach ($lineItems as $lineItem) {
                $shippingMethod = $lineItem->getShippingMethod();

                if (in_array($shippingMethod, $checkedMethods)) {
                    continue;
                }

                $ruleExists = $this->repository->getEnabledRulesByMethod($shippingMethod);
                if ($ruleExists) {
                    array_push($checkedMethods, $shippingMethod);
                } else {
                    $valid = false;
                }
            }
        }

        return $valid;
    }

    public function getName()
    {
        return self::CONDITION_NAME;
    }

    public function initialize(array $options)
    {
        if (array_key_exists(self::OPTION_ENTITY, $options)) {
            $this->entity = $options[self::OPTION_ENTITY];
        }

        if (array_key_exists(0, $options)) {
            $this->entity = $options[0];
        }

        if (!$this->entity) {
            throw new InvalidArgumentException(sprintf('Missing "%s" option', self::OPTION_ENTITY));
        }

        return $this;
    }

    public function toArray()
    {
        return $this->convertToArray([$this->entity]);
    }

    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->entity], $factoryAccessor);
    }
}

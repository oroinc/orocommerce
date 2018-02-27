<?php

namespace Oro\Bundle\CheckoutBundle\Model\Condition;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

class OrderLineItemsHasCount extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'order_line_item_has_count';
    const CONFIG_VISIBILITY_PATH_ORDER = 'oro_order.frontend_product_visibility';
    const CONFIG_VISIBILITY_PATH_RFP = 'oro_rfp.frontend_product_visibility';

    /** @var CheckoutInterface */
    protected $entity;

    /** @var CheckoutLineItemsManager */
    protected $checkoutLineItemsManager;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     */
    public function __construct(CheckoutLineItemsManager $checkoutLineItemsManager)
    {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $entity = $this->resolveValue($context, $this->entity);

        if (!$entity instanceof CheckoutInterface) {
            throw new Exception\InvalidArgumentException(sprintf('Entity must implement %s', CheckoutInterface::class));
        }
        $lineItems = $this->checkoutLineItemsManager->getData($entity, false, static::CONFIG_VISIBILITY_PATH_ORDER);
        $result = !$lineItems->isEmpty();

        if (!$result) {
            $lineItemsForRfp = $this->checkoutLineItemsManager->getData(
                $entity,
                false,
                static::CONFIG_VISIBILITY_PATH_RFP
            );
            $message = $lineItemsForRfp->isEmpty()
                ? 'oro.checkout.workflow.condition.order_line_item_has_count_not_allow_rfp.message'
                : 'oro.checkout.workflow.condition.order_line_item_has_count_allow_rfp.message';
            $this->setMessage($message);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (count($options) !== 1) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 elements, but %d given.', count($options))
            );
        }

        if (isset($options['entity'])) {
            $this->entity = $options['entity'];
        } elseif (isset($options[0])) {
            $this->entity = $options[0];
        } else {
            throw new Exception\InvalidArgumentException('Option "entity" must be set.');
        }
    }
}

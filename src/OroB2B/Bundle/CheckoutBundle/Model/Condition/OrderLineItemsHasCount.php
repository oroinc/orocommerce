<?php

namespace OroB2B\Bundle\CheckoutBundle\Model\Condition;

use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;

class OrderLineItemsHasCount extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'order_line_item_has_count';

    /**
     * @var
     */
    protected $entity;

    /**
     * @var CheckoutLineItemsManager
     */
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
            throw new Exception\InvalidArgumentException(
                'Entity must implement OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface'
            );
        }
        $lineItems = $this->checkoutLineItemsManager->getData($entity);
        return count($lineItems) > 0;
    }

    /**
     * Returns the expression name.
     *
     * @return string
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

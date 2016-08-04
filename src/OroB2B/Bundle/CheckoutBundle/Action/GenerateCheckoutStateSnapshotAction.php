<?php

namespace OroB2B\Bundle\CheckoutBundle\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;

class GenerateCheckoutStateSnapshotAction extends AbstractAction
{
    const OPTION_KEY_CHECKOUT = 'checkout';
    const OPTION_KEY_ATTRIBUTE = 'attribute';

    /** @var array */
    protected $options;

    /** @var CheckoutStateDiffManager */
    protected $diffManager;

    /**
     * @param ContextAccessor $contextAccessor
     * @param CheckoutStateDiffManager $diffManager
     */
    public function __construct(ContextAccessor $contextAccessor, CheckoutStateDiffManager $diffManager)
    {
        $this->diffManager = $diffManager;

        parent::__construct($contextAccessor);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $checkoutPath = $this->getOption($this->options, self::OPTION_KEY_CHECKOUT);
        $attributePath = $this->getOption($this->options, self::OPTION_KEY_ATTRIBUTE);

        $checkout = $this->contextAccessor->getValue($context, $checkoutPath);

        if (!is_object($checkout) || !$checkout instanceof Checkout) {
            throw new InvalidParameterException(
                sprintf(
                    'Entity must be instance of "%s", "%s" given',
                    Checkout::class,
                    is_object($checkout) ? get_class($checkout) : gettype($checkout)
                )
            );
        }

        $state = $this->diffManager->getCurrentState($checkout);

        $this->contextAccessor->setValue($context, $attributePath, $state);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_CHECKOUT])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', self::OPTION_KEY_CHECKOUT));
        }

        if (empty($options[self::OPTION_KEY_ATTRIBUTE])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', self::OPTION_KEY_ATTRIBUTE));
        }

        $this->options = $options;

        return $this;
    }
}

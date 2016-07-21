<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class AlternativeCheckoutByQuote extends AbstractAction
{
    const QUOTE = 'quote';
    const CHECKOUT_ATTRIBUTE = 'checkout';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ManagerRegistry $registry
     * @param ContextAccessor $contextAccessor
     */
    public function __construct(ManagerRegistry $registry, ContextAccessor $contextAccessor)
    {
        $this->registry = $registry;
        parent::__construct($contextAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::QUOTE])) {
            throw new InvalidParameterException('Checkout name parameter is required');
        }
        if (empty($options[self::CHECKOUT_ATTRIBUTE])) {
            throw new InvalidParameterException('checkout parameter is required');
        }
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        /** @var Checkout $checkout */
        $quote = $this->contextAccessor->getValue($context, $this->options[self::QUOTE]);
        $checkout = $this->registry
            ->getManagerForClass('OroB2BCheckoutBundle:Checkout')
            ->getRepository('OroB2BCheckoutBundle:Checkout')
            ->getCheckoutByQuote($quote);

        $this->contextAccessor->setValue($context, $this->options[self::CHECKOUT_ATTRIBUTE], $checkout);
    }
}

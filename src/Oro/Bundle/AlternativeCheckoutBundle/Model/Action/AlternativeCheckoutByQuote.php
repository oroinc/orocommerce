<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

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
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry,
        SecurityContextInterface $securityContext
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::QUOTE])) {
            throw new InvalidParameterException(sprintf('Parameter `%s` is required', self::QUOTE));
        }
        if (empty($options[self::CHECKOUT_ATTRIBUTE])) {
            throw new InvalidParameterException(sprintf('Parameter `%s` is required', self::CHECKOUT_ATTRIBUTE));
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
        $checkout = $this->getRepository()->getCheckoutByQuote($quote, $this->getUser());

        $this->contextAccessor->setValue($context, $this->options[self::CHECKOUT_ATTRIBUTE], $checkout);
    }

    /**
     * @return AccountUser|null
     */
    protected function getUser()
    {
        if (null === ($token = $this->securityContext->getToken())) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * @return CheckoutRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass('OroCheckoutBundle:Checkout')
            ->getRepository('OroCheckoutBundle:Checkout');
    }
}

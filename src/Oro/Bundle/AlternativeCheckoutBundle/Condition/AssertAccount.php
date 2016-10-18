<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Condition;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class AssertAccount extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /**
     * @var int
     */
    protected $accountId;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $user = $this->securityFacade->getLoggedUser();

        return $user instanceof AccountUser
            && ($this->resolveValue($context, $this->accountId) === $user->getAccount()->getId());
    }

    /**
     * Returns the expression name.
     *
     * @return string
     */
    public function getName()
    {
        return 'assert_account';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 !== count($options)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 elements, but %d given.', count($options))
            );
        }
        $this->accountId = $options[0];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->accountId]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->accountId, $factoryAccessor);
    }
}

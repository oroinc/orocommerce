<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Model;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class UserInGroupCondition extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /**
     * @var int
     */
    protected $groupId;

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
            && ($this->resolveValue($context, $this->groupId) === $user->getAccount()->getGroup()->getId());
    }

    /**
     * Returns the expression name.
     *
     * @return string
     */
    public function getName()
    {
        return 'assert_user_group';
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
        $this->groupId = $options[0];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->groupId]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->groupId, $factoryAccessor);
    }
}

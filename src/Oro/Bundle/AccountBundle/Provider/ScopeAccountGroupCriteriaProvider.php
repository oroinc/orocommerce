<?php

namespace Oro\Bundle\AccountBundle\Provider;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class ScopeAccountGroupCriteriaProvider implements ScopeCriteriaProviderInterface
{
    const FIELD_NAME = 'accountGroup';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

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
    public function getCriteriaByContext($context)
    {
        if (is_object($context) || is_array($context)) {
            $accountGroup = $this->getValue($context, 'accountGroup');
            if ($accountGroup instanceof AccountGroup || ScopeCriteria::IS_NOT_NULL === $accountGroup) {
                return ['accountGroup' => $accountGroup];
            }

            $account = $this->getValue($context, 'account');
            if ($account instanceof Account && null !== $account->getGroup()) {
                return ['accountGroup' => $account->getGroup()];
            }
        }

        return [];
    }

    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        $loggedUser = $this->securityFacade->getLoggedUser();
        if (null !== $loggedUser
            && $loggedUser instanceof AccountUser
            && null !== $loggedUser->getAccount()
        ) {
            return [$this->getCriteriaField() => $loggedUser->getAccount()->getGroup()];
        }

        return [];
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @param object|array $context
     * @param string $propertyPath
     * @return mixed|null
     */
    protected function getValue($context, $propertyPath)
    {
        try {
            return $value = $this->getPropertyAccessor()
                ->getValue($context, $propertyPath);
        } catch (NoSuchPropertyException $e) {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getCriteriaField()
    {
        return self::FIELD_NAME;
    }
}

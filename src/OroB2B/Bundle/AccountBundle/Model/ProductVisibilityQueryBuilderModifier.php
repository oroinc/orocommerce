<?php

namespace OroB2B\Bundle\AccountBundle\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;

class ProductVisibilityQueryBuilderModifier
{
    /**
     * @var string
     */
    protected $visibilitySystemConfigurationPath;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param ConfigManager $configManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ConfigManager $configManager, TokenStorageInterface $tokenStorage)
    {
        $this->configManager = $configManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param string $visibilitySystemConfigurationPath
     */
    public function setVisibilitySystemConfigurationPath($visibilitySystemConfigurationPath)
    {
        $this->visibilitySystemConfigurationPath = $visibilitySystemConfigurationPath;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function modify(QueryBuilder $queryBuilder)
    {
        $visibilities = [$this->getProductVisibilityResolvedTerm($queryBuilder)];

        $user = $this->getAccountUserIfApplicable();
        if ($user) {
            $visibilities[] = $this->getAccountGroupProductVisibilityResolvedTerm(
                $queryBuilder,
                $user->getAccount()->getGroup()
            );
            $visibilities[] = $this->getAccountProductVisibilityResolvedTerm($queryBuilder, $user->getAccount());
        }

        $queryBuilder->andWhere($queryBuilder->expr()->gt(implode(' + ', $visibilities), 0));
    }

    /**
     * @return AccountUser|null
     */
    protected function getAccountUserIfApplicable()
    {
        $token = $this->tokenStorage->getToken();
        /** @var AccountUser $user */
        if ($token && ($user = $token->getUser()) instanceof AccountUser
            && $user->getAccount() && $user->getAccount()->getGroup()
        ) {
            return $user;
        }

        return null;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function getProductVisibilityResolvedTerm(QueryBuilder $queryBuilder)
    {
        $queryBuilder->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved',
            'product_visibility_resolved',
            Join::WITH,
            sprintf('%s = product_visibility_resolved.product', $this->getRootAlias($queryBuilder))
        );

        return sprintf('coalesce(product_visibility_resolved.visibility, %s)', $this->getConfigValue());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param AccountGroup $account
     * @return string
     */
    protected function getAccountGroupProductVisibilityResolvedTerm(QueryBuilder $queryBuilder, AccountGroup $account)
    {
        $queryBuilder->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved',
            'account_group_product_visibility',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_group_product_visibility.product'
                ),
                $queryBuilder->expr()->eq('account_group_product_visibility.accountGroup', ':_account_group')
            )
        );

        $queryBuilder->setParameter('_account_group', $account);

        $term = <<<TERM
CASE WHEN account_group_product_visibility.visibility = 0
  THEN (%s * 10)
ELSE (COALESCE(account_group_product_visibility.visibility, 0) * 10)
END
TERM;
        return sprintf($term, $this->getConfigValue());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Account $account
     * @return string
     */
    protected function getAccountProductVisibilityResolvedTerm(QueryBuilder $queryBuilder, Account $account)
    {
        $queryBuilder->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved',
            'account_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.account', ':_account')
            )
        );

        $queryBuilder->setParameter('_account', $account);

        $term = <<<TERM
CASE WHEN account_product_visibility_resolved.visibility = 0
  THEN (%s * 100)
ELSE
  CASE WHEN account_product_visibility_resolved.visibility = 2
    THEN (product_visibility_resolved.visibility * 100)
  ELSE (COALESCE(account_product_visibility_resolved.visibility, 0) * 100)
  END
END
TERM;
        return sprintf($term, $this->getConfigValue());
    }

    /**
     * @return integer
     */
    protected function getConfigValue()
    {
        if (!$this->visibilitySystemConfigurationPath) {
            throw new \LogicException(
                sprintf('%s::visibilitySystemConfigurationPath not configured', get_class($this))
            );
        }

        $configVisibility = $this->configManager->get($this->visibilitySystemConfigurationPath);
        return ($configVisibility === ProductVisibility::VISIBLE) ? 1 : -1;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return mixed
     */
    protected function getRootAlias(QueryBuilder $queryBuilder)
    {
        return $queryBuilder->getRootAliases()[0];
    }
}

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
        $productVisibilityResolvedAlias = $this->joinProductVisibilityResolved($queryBuilder);

        $visibilities = [$this->getProductVisibilityResolvedTerm($productVisibilityResolvedAlias)];

        $token = $this->tokenStorage->getToken();
        /** @var AccountUser $user */
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            $accountGroupProductVisibilityResolvedAlias = $this->joinAccountGroupProductVisibilityResolved(
                $queryBuilder,
                $user->getAccount()->getGroup()
            );
            $accountProductVisibilityResolvedAlias = $this->joinAccountProductVisibilityResolved(
                $queryBuilder,
                $user->getAccount()
            );

            $visibilities[] = $this->getAccountGroupProductVisibilityResolvedTerm(
                $accountGroupProductVisibilityResolvedAlias
            );
            $visibilities[] = $this->getAccountProductVisibilityResolvedTerm(
                $accountProductVisibilityResolvedAlias,
                $productVisibilityResolvedAlias
            );
        }

        $queryBuilder->andWhere($queryBuilder->expr()->gt(implode(' + ', $visibilities), 0));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function joinProductVisibilityResolved(QueryBuilder $queryBuilder)
    {
        $tableAlias = $this->getTableAlias($queryBuilder, 'pvr');

        $queryBuilder->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved',
            $tableAlias,
            Join::WITH,
            sprintf('%s = %s.product', $this->getRootAlias($queryBuilder), $tableAlias)
        );

        return $tableAlias;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param AccountGroup $account
     * @return string
     */
    protected function joinAccountGroupProductVisibilityResolved(QueryBuilder $queryBuilder, AccountGroup $account)
    {
        $tableAlias = $this->getTableAlias($queryBuilder, 'agpvr');

        $parameterName = $this->getParameterName($queryBuilder, 'account_group');

        $queryBuilder->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved',
            $tableAlias,
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq($this->getRootAlias($queryBuilder), $tableAlias . '.product'),
                $queryBuilder->expr()->eq($tableAlias . '.accountGroup', ':' . $parameterName)
            )
        );

        $queryBuilder->setParameter($parameterName, $account);

        return $tableAlias;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Account $account
     * @return string
     */
    protected function joinAccountProductVisibilityResolved(QueryBuilder $queryBuilder, Account $account)
    {
        $tableAlias = $this->getTableAlias($queryBuilder, 'apvr');

        $parameterName = $this->getParameterName($queryBuilder, 'account');

        $queryBuilder->leftJoin(
            'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved',
            $tableAlias,
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq($this->getRootAlias($queryBuilder), $tableAlias . '.product'),
                $queryBuilder->expr()->eq($tableAlias . '.account', ':' . $parameterName)
            )
        );

        $queryBuilder->setParameter($parameterName, $account);

        return $tableAlias;
    }

    /**
     * @param string $productVisibilityResolvedAlias
     * @return string
     */
    protected function getProductVisibilityResolvedTerm($productVisibilityResolvedAlias)
    {
        return sprintf('coalesce(%s.visibility, %s)', $productVisibilityResolvedAlias, $this->getConfigValue());
    }

    /**
     * @param string $accountGroupProductVisibilityResolvedAlias
     * @return mixed
     */
    protected function getAccountGroupProductVisibilityResolvedTerm($accountGroupProductVisibilityResolvedAlias)
    {
        $term = <<<TERM
CASE WHEN %alias%.visibility = 0
  THEN (%config_value% * 10)
ELSE (COALESCE(%alias%.visibility, 0) * 10)
END
TERM;
        return strtr($term, [
            '%alias%' => $accountGroupProductVisibilityResolvedAlias,
            '%config_value%' => $this->getConfigValue(),
        ]);
    }

    /**
     * @param string $productVisibilityResolvedAlias
     * @param string $accountProductVisibilityResolvedAlias
     * @return mixed
     */
    protected function getAccountProductVisibilityResolvedTerm(
        $accountProductVisibilityResolvedAlias,
        $productVisibilityResolvedAlias
    ) {
        $term = <<<TERM
CASE WHEN %alias%.visibility = 0
  THEN (%config_value% * 100)
ELSE
  CASE WHEN %alias%.visibility = 2
    THEN (%pvr_alias%.visibility * 100)
  ELSE (COALESCE(%alias%.visibility, 0) * 100)
  END
END
TERM;
        return strtr($term, [
            '%alias%' => $accountProductVisibilityResolvedAlias,
            '%pvr_alias%' => $productVisibilityResolvedAlias,
            '%config_value%' => $this->getConfigValue(),
        ]);
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
     * @param string $prefix
     * @return string
     */
    protected function getTableAlias(QueryBuilder $queryBuilder, $prefix)
    {
        return $prefix . '_' . (count($queryBuilder->getDQLPart('join')) + 1);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $prefix
     * @return string
     */
    protected function getParameterName(QueryBuilder $queryBuilder, $prefix)
    {
        return $prefix . '_' . ($queryBuilder->getParameters()->count() + 1);
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

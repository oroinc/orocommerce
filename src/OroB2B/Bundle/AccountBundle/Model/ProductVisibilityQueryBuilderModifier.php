<?php

namespace OroB2B\Bundle\AccountBundle\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\AccountBundle\Provider\AccountUserRelationsProvider;

class ProductVisibilityQueryBuilderModifier
{
    /**
     * @var string
     */
    protected $productConfigPath;

    /**
     * @var string
     */
    protected $categoryConfigPath;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var AccountUserRelationsProvider
     */
    protected $relationsProvider;

    /**
     * @var array
     */
    protected $configValue = [];

    /**
     * @param ConfigManager $configManager
     * @param TokenStorageInterface $tokenStorage
     * @param WebsiteManager $websiteManager
     * @param AccountUserRelationsProvider $relationsProvider
     */
    public function __construct(
        ConfigManager $configManager,
        TokenStorageInterface $tokenStorage,
        WebsiteManager $websiteManager,
        AccountUserRelationsProvider $relationsProvider
    ) {
        $this->configManager = $configManager;
        $this->tokenStorage = $tokenStorage;
        $this->websiteManager = $websiteManager;
        $this->relationsProvider = $relationsProvider;
    }

    /**
     * @param string $path
     */
    public function setProductVisibilitySystemConfigurationPath($path)
    {
        $this->productConfigPath = $path;
    }

    /**
     * @param string $path
     */
    public function setCategoryVisibilitySystemConfigurationPath($path)
    {
        $this->categoryConfigPath = $path;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function modify(QueryBuilder $queryBuilder)
    {
        $accountUser = $this->getAccountUser();
        $visibilities = [$this->getProductVisibilityResolvedTerm($queryBuilder)];

        $accountGroup = $this->relationsProvider->getAccountGroup($accountUser);
        if ($accountGroup) {
            $visibilities[] = $this->getAccountGroupProductVisibilityResolvedTerm(
                $queryBuilder,
                $accountGroup
            );
        }

        $account = $this->relationsProvider->getAccount($accountUser);
        if ($account) {
            $visibilities[] = $this->getAccountProductVisibilityResolvedTerm($queryBuilder, $account);
        }

        $queryBuilder->andWhere($queryBuilder->expr()->gt(implode(' + ', $visibilities), 0));
    }

    /**
     * @return AccountUser|null
     */
    protected function getAccountUser()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
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
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq($this->getRootAlias($queryBuilder), 'product_visibility_resolved.product'),
                $queryBuilder->expr()->eq('product_visibility_resolved.website', ':_website')
            )
        );

        $queryBuilder->setParameter('_website', $this->websiteManager->getCurrentWebsite());

        return sprintf(
            'COALESCE(%s, %s)',
            $this->addCategoryConfigFallback('product_visibility_resolved.visibility'),
            $this->getProductConfigValue()
        );
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
            'account_group_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_group_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('account_group_product_visibility_resolved.accountGroup', ':_account_group'),
                $queryBuilder->expr()->eq('account_group_product_visibility_resolved.website', ':_website')
            )
        );

        $queryBuilder->setParameter('_account_group', $account);
        $queryBuilder->setParameter('_website', $this->websiteManager->getCurrentWebsite());

        return sprintf(
            'COALESCE(%s, 0) * 10',
            $this->addCategoryConfigFallback('account_group_product_visibility_resolved.visibility')
        );
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
                $queryBuilder->expr()->eq('account_product_visibility_resolved.account', ':_account'),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.website', ':_website')
            )
        );

        $queryBuilder->setParameter('_account', $account);
        $queryBuilder->setParameter('_website', $this->websiteManager->getCurrentWebsite());

        $productFallback = $this->addCategoryConfigFallback('product_visibility_resolved.visibility');
        $accountFallback = $this->addCategoryConfigFallback('account_product_visibility_resolved.visibility');

        $term = <<<TERM
CASE WHEN account_product_visibility_resolved.visibility = %s
    THEN (COALESCE(%s, %s) * 100)
ELSE (COALESCE(%s, 0) * 100)
END
TERM;
        return sprintf(
            $term,
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $productFallback,
            $this->getProductConfigValue(),
            $accountFallback
        );
    }

    /**
     * @return int
     */
    protected function getProductConfigValue()
    {
        return $this->getConfigValue($this->productConfigPath);
    }

    /**
     * @return int
     */
    protected function getCategoryConfigValue()
    {
        return $this->getConfigValue($this->categoryConfigPath);
    }

    /**
     * @param string $path
     * @return integer
     */
    protected function getConfigValue($path)
    {
        if (!empty($this->configValue[$path])) {
            return $this->configValue[$path];
        }

        if (!$this->productConfigPath) {
            throw new \LogicException(
                sprintf('%s::productConfigPath not configured', get_class($this))
            );
        }
        if (!$this->categoryConfigPath) {
            throw new \LogicException(
                sprintf('%s::categoryConfigPath not configured', get_class($this))
            );
        }

        $this->configValue = [
            $this->productConfigPath => $this->configManager->get($this->productConfigPath),
            $this->categoryConfigPath => $this->configManager->get($this->categoryConfigPath),
        ];

        foreach ($this->configValue as $key => $value) {
            $this->configValue[$key] = $value === VisibilityInterface::VISIBLE
                ? BaseVisibilityResolved::VISIBILITY_VISIBLE
                : BaseVisibilityResolved::VISIBILITY_HIDDEN;
        }

        return $this->configValue[$path];
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return mixed
     */
    protected function getRootAlias(QueryBuilder $queryBuilder)
    {
        return $queryBuilder->getRootAliases()[0];
    }

    /**
     * @param string $field
     * @return string
     */
    protected function addCategoryConfigFallback($field)
    {
        return sprintf(
            'CASE WHEN %1$s = %2$s THEN %3$s ELSE %1$s END',
            $field,
            BaseVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $this->getCategoryConfigValue()
        );
    }
}

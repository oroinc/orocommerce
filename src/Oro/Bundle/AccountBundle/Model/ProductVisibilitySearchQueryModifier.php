<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Provider\AccountUserRelationsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\AccountBundle\Entity\AccountUser;

class ProductVisibilitySearchQueryModifier
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AccountUserRelationsProvider
     */
    protected $relationsProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param TokenStorageInterface        $tokenStorage
     * @param AccountUserRelationsProvider $relationsProvider
     * @param ConfigManager                $configManager
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AccountUserRelationsProvider $relationsProvider,
        ConfigManager $configManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->relationsProvider = $relationsProvider;
        $this->configManager = $configManager;
    }

    /**
     * @param Query $query
     */
    public function modify(Query $query)
    {
        $accountUser = $this->getAccountUser();
        $accountGroup = $this->relationsProvider->getAccountGroup($accountUser);

        $query->getCriteria()->andWhere(
            $this->createProductVisibilityExpression($accountUser, $accountGroup)
        );
    }

    /**
     * @param AccountUser|null $accountUser
     * @param AccountGroup|null $accountGroup
     * @return CompositeExpression
     */
    protected function createProductVisibilityExpression(
        AccountUser $accountUser = null,
        AccountGroup $accountGroup = null
    ) {
        list($defaultField, $accountField) = $this->getVisibilityFields($accountUser, $accountGroup);

        $exprBuilder = Criteria::expr();
        $expression = $exprBuilder->orX(
            $exprBuilder->andX(
                $exprBuilder->eq($defaultField, 1),
                $exprBuilder->isNull($accountField)
            ),
            $exprBuilder->andX(
                $exprBuilder->eq($defaultField, 0),
                $exprBuilder->eq($accountField, 1)
            )
        );

        return $expression;
    }

    /**
     * @param AccountUser $accountUser
     * @param AccountGroup $accountGroup
     * @return string
     */
    protected function getVisibilityFields(
        AccountUser $accountUser = null,
        AccountGroup $accountGroup = null
    ) {
        $accountField = 'visibility_anonymous';
        if ($accountGroup instanceof AccountGroup &&
            $accountGroup->getId() !== (int)$this->configManager->get('oro_account.anonymous_account_group')
        ) {
            $accountField = sprintf('visibility_account_%s', $accountUser->getId());
        }

        return [
            Criteria::implodeFieldTypeName(Query::TYPE_INTEGER, $accountField),
            Criteria::implodeFieldTypeName(Query::TYPE_INTEGER, 'is_visible_by_default'),
        ];
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
}

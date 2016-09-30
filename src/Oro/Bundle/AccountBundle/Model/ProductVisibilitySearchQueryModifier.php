<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Collections\Expr\CompositeExpression;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\AccountBundle\EventListener\WebsiteSearchProductVisibilityIndexerListener;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductVisibilitySearchQueryModifier
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Query $query
     */
    public function modify(Query $query)
    {
        $query->getCriteria()->andWhere($this->createProductVisibilityExpression());
    }

    /**
     * @return CompositeExpression
     */
    protected function createProductVisibilityExpression()
    {
        $exprBuilder = Criteria::expr();
        $accountUser = $this->getAccountUser();

        if ($accountUser) {
            $accountField = $this->completeFieldName(
                sprintf(
                    '%s_%s',
                    WebsiteSearchProductVisibilityIndexerListener::FIELD_VISIBILITY_ACCOUNT,
                    $accountUser->getId()
                )
            );
            $defaultField = $this->completeFieldName(
                WebsiteSearchProductVisibilityIndexerListener::FIELD_IS_VISIBLE_BY_DEFAULT
            );

            $expression = $exprBuilder->orX(
                $exprBuilder->andX(
                    $exprBuilder->eq($defaultField, BaseVisibilityResolved::VISIBILITY_VISIBLE),
                    $exprBuilder->isNull($accountField)
                ),
                $exprBuilder->andX(
                    $exprBuilder->eq($defaultField, BaseVisibilityResolved::VISIBILITY_HIDDEN),
                    $exprBuilder->eq(
                        $accountField,
                        WebsiteSearchProductVisibilityIndexerListener::ACCOUNT_VISIBILITY_VALUE
                    )
                )
            );
        } else {
            $field = $this->completeFieldName(
                WebsiteSearchProductVisibilityIndexerListener::FIELD_VISIBILITY_ANONYMOUS
            );
            $expression = $exprBuilder->eq($field, BaseVisibilityResolved::VISIBILITY_VISIBLE);
        }


        return $expression;
    }

    /**
     * @param $name
     * @return string
     */
    protected function completeFieldName($name)
    {
        return Criteria::implodeFieldTypeName(Query::TYPE_INTEGER, $name);
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

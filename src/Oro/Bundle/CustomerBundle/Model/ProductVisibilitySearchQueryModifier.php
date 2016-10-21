<?php

namespace Oro\Bundle\CustomerBundle\Model;

use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\CustomerBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryModifierInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AccountIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProductVisibilitySearchQueryModifier implements QueryModifierInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var PlaceholderProvider
     */
    private $placeholderProvider;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param PlaceholderProvider $placeholderProvider
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        PlaceholderProvider $placeholderProvider
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->placeholderProvider = $placeholderProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function modify(Query $query)
    {
        $query->getCriteria()->andWhere($this->createProductVisibilityExpression());
    }

    /**
     * @return CompositeExpression
     */
    private function createProductVisibilityExpression()
    {
        $exprBuilder = Criteria::expr();
        $account = $this->getAccount();

        if ($account) {
            $accountField = $this->placeholderProvider->getPlaceholderFieldName(
                Product::class,
                ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
                [
                   AccountIdPlaceholder::NAME => $account->getId()
                ]
            );

            $accountField = $this->completeFieldName($accountField);

            $defaultField = $this->completeFieldName(ProductVisibilityIndexer::FIELD_IS_VISIBLE_BY_DEFAULT);

            $expression = $exprBuilder->orX(
                $exprBuilder->andX(
                    $exprBuilder->eq($defaultField, BaseVisibilityResolved::VISIBILITY_VISIBLE),
                    $exprBuilder->notExists($accountField)
                ),
                $exprBuilder->andX(
                    $exprBuilder->eq($defaultField, BaseVisibilityResolved::VISIBILITY_HIDDEN),
                    $exprBuilder->eq(
                        $accountField,
                        ProductVisibilityIndexer::ACCOUNT_VISIBILITY_VALUE
                    )
                )
            );
        } else {
            $field = $this->completeFieldName(ProductVisibilityIndexer::FIELD_VISIBILITY_ANONYMOUS);
            $expression = $exprBuilder->eq($field, BaseVisibilityResolved::VISIBILITY_VISIBLE);
        }

        return $expression;
    }

    /**
     * @param $name
     * @return string
     */
    private function completeFieldName($name)
    {
        return Criteria::implodeFieldTypeName(Query::TYPE_INTEGER, $name);
    }

    /**
     * @return AccountUser|null
     */
    private function getAccount()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            return $user->getAccount();
        }

        return null;
    }
}

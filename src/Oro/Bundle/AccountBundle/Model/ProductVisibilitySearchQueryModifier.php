<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\AccountBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AccountIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderFieldsProvider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProductVisibilitySearchQueryModifier
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var PlaceholderFieldsProvider
     */
    private $placeholderFieldsProvider;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param PlaceholderFieldsProvider $placeholderFieldsProvider
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        PlaceholderFieldsProvider $placeholderFieldsProvider
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->placeholderFieldsProvider = $placeholderFieldsProvider;
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
    private function createProductVisibilityExpression()
    {
        $exprBuilder = Criteria::expr();
        $account = $this->getAccount();

        if ($account) {
            $accountField = $this->placeholderFieldsProvider->getPlaceholderFieldName(
                Product::class,
                ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
                [
                   AccountIdPlaceholder::NAME => $account->getId()
                ]
            );

            $accountField = $this->completeFieldName($accountField);

            $defaultField = $this->completeFieldName(ProductVisibilityIndexer::FIELD_IS_VISIBLE_BY_DEFAULT);

            //TODO: Replace isNull operator after BB-4508 is finished
            $expression = $exprBuilder->orX(
                $exprBuilder->andX(
                    $exprBuilder->eq($defaultField, BaseVisibilityResolved::VISIBILITY_VISIBLE),
                    $exprBuilder->isNull($accountField)
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

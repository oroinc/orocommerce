<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Placeholder\CustomerIdPlaceholder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryModifierInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
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
        $customer = $this->getCustomer();

        if ($customer) {
            $customerField = $this->placeholderProvider->getPlaceholderFieldName(
                Product::class,
                ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
                [
                   CustomerIdPlaceholder::NAME => $customer->getId()
                ]
            );

            $customerField = $this->completeFieldName($customerField);

            $defaultField = $this->completeFieldName(ProductVisibilityIndexer::FIELD_IS_VISIBLE_BY_DEFAULT);

            $expression = $exprBuilder->orX(
                $exprBuilder->andX(
                    $exprBuilder->eq($defaultField, BaseVisibilityResolved::VISIBILITY_VISIBLE),
                    $exprBuilder->notExists($customerField)
                ),
                $exprBuilder->andX(
                    $exprBuilder->eq($defaultField, BaseVisibilityResolved::VISIBILITY_HIDDEN),
                    $exprBuilder->eq(
                        $customerField,
                        BaseVisibilityResolved::VISIBILITY_VISIBLE
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
     * @return CustomerUser|null
     */
    private function getCustomer()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof CustomerUser) {
            return $user->getCustomer();
        }

        return null;
    }
}

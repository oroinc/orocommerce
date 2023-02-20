<?php

namespace Oro\Bundle\VisibilityBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\CategoryVisibilityProvider;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies a category query builder to filter invisible categories,
 * because they should not be accessible via API for the storefront.
 * @see \Oro\Bundle\VisibilityBundle\EventListener\CategoryTreeHandlerListener
 */
class CategoryVisibilityQueryModifier implements QueryModifierInterface
{
    private EntityClassResolver $entityClassResolver;
    private CategoryVisibilityProvider $categoryVisibilityProvider;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        EntityClassResolver $entityClassResolver,
        CategoryVisibilityProvider $categoryVisibilityProvider,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->categoryVisibilityProvider = $categoryVisibilityProvider;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        if ($skipRootEntity) {
            return;
        }

        $categoryAlias = null;
        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            if (Category::class === $this->entityClassResolver->getEntityClass($from->getFrom())) {
                $categoryAlias = $from->getAlias();
                break;
            }
        }

        if ($categoryAlias) {
            $hiddenCategoryIds = $this->categoryVisibilityProvider->getHiddenCategoryIds(
                $this->getCustomerUser()
            );
            if ($hiddenCategoryIds) {
                $this->applyRootRestriction($qb, $categoryAlias, $hiddenCategoryIds);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $rootAlias
     * @param int[]        $hiddenCategoryIds
     */
    private function applyRootRestriction(QueryBuilder $qb, string $rootAlias, array $hiddenCategoryIds): void
    {
        QueryBuilderUtil::checkIdentifier($rootAlias);
        $paramName = QueryBuilderUtil::generateParameterName('hiddenCategoryIds');
        $qb
            ->andWhere($qb->expr()->notIn($rootAlias, ':' . $paramName))
            ->setParameter($paramName, $hiddenCategoryIds);
    }

    private function getCustomerUser(): ?CustomerUser
    {
        $customerUser = null;
        $user = $this->tokenAccessor->getUser();
        if ($user instanceof CustomerUser) {
            $customerUser = $user;
        }

        return $customerUser;
    }
}

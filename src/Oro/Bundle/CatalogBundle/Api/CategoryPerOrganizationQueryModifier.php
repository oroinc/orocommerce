<?php

namespace Oro\Bundle\CatalogBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies a category query builder to filter categories from not the current organization,
 * because they should not be accessible via API for the storefront.
 * @see \Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider
 * This class can be implemented as a rule for AclHelper after this component
 * will allow to add additional rules for it (BAP-10836). In this case we will modify AST of a query
 * instead of modifying QueryBuilder; this solution is more flexible and more error-free
 * because we will work with already parsed query, instead of trying to parse it manually.
 */
class CategoryPerOrganizationQueryModifier implements QueryModifierInterface
{
    /** @var EntityClassResolver */
    private $entityClassResolver;

    /** @var WebsiteManager */
    private $websiteManager;

    /**
     * @param EntityClassResolver $entityClassResolver
     * @param WebsiteManager      $websiteManager
     */
    public function __construct(
        EntityClassResolver $entityClassResolver,
        WebsiteManager $websiteManager
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
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
            $organizationId = $this->getOrganizationId();
            if (null !== $organizationId) {
                $this->applyRootRestriction($qb, $categoryAlias, $organizationId);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $rootAlias
     * @param int          $organizationId
     */
    private function applyRootRestriction(QueryBuilder $qb, string $rootAlias, int $organizationId): void
    {
        QueryBuilderUtil::checkIdentifier($rootAlias);
        $paramName = QueryBuilderUtil::generateParameterName('organizationId');
        $qb
            ->andWhere($qb->expr()->eq($rootAlias . '.organization', ':' . $paramName))
            ->setParameter($paramName, $organizationId);
    }

    /**
     * @return int|null
     */
    private function getOrganizationId(): ?int
    {
        $website = $this->websiteManager->getCurrentWebsite();
        if (!$website) {
            return null;
        }

        $organization = $website->getOrganization();
        if (!$organization) {
            return null;
        }

        return $organization->getId();
    }
}

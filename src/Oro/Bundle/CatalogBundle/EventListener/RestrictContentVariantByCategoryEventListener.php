<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntitiesEvent;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntityEvent;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Restrict content variant query builder by a given category or by a set of categories.
 */
class RestrictContentVariantByCategoryEventListener
{
    private const string ASSOCIATION = 'category_page_category';

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function applyRestriction(RestrictContentVariantByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Category) {
            $excludeSubcategories = false;
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $excludeSubcategories = !$request->get('includeSubcategories', true);
            }

            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq(
                    QueryBuilderUtil::getField($event->getVariantAlias(), self::ASSOCIATION),
                    ':category'
                ))
                ->andWhere($queryBuilder->expr()->eq(
                    QueryBuilderUtil::getField($event->getVariantAlias(), 'exclude_subcategories'),
                    ':excludeSubcategories'
                ))
                ->setParameter('category', $entity)
                ->setParameter('excludeSubcategories', $excludeSubcategories, Types::BOOLEAN);
        }
    }

    public function applyRestrictionForEntities(RestrictContentVariantByEntitiesEvent $event): void
    {
        if (!is_a($event->getEntityClass(), Category::class, true)) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();
        $field = QueryBuilderUtil::getField($event->getVariantAlias(), self::ASSOCIATION);
        $queryBuilder
            ->addSelect(QueryBuilderUtil::sprintf('IDENTITY(%s) AS %s', $field, $event->getOwnerIdAlias()))
            ->andWhere($queryBuilder->expr()->in($field, ':entityIds'))
            ->setParameter('entityIds', $event->getEntityIds(), ArrayParameterType::INTEGER);

        $event->setRestricted(true);
    }
}

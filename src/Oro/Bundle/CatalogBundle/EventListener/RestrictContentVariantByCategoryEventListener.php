<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntityEvent;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Restrict content variant query builder by a given category.
 */
class RestrictContentVariantByCategoryEventListener
{
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
                    QueryBuilderUtil::getField($event->getVariantAlias(), 'category_page_category'),
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
}

<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapCategoryListener;
use Oro\Bundle\VisibilityBundle\Model\CategoryVisibilityQueryBuilderModifier;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class RestrictSitemapCategoryListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testFilterHiddenForAnonymousCategories()
    {
        $organization = new Organization();
        $website = new Website();
        $website->setOrganization($organization);

        $expr = $this->createMock(Expr::class);
        $expr->expects($this->once())
            ->method('eq')
            ->with('entityAlias.organization', ':organization')
            ->willReturnSelf();

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($expr);
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with($expr);
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('organization', $organization);

        /** @var CategoryVisibilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject $queryModifier */
        $queryModifier = $this->createMock(CategoryVisibilityQueryBuilderModifier::class);
        $queryModifier->expects($this->once())
            ->method('restrictForAnonymous')
            ->with($queryBuilder);
        $version = 1;
        $event = new RestrictSitemapEntitiesEvent($queryBuilder, $version, $website);
        $listener = new RestrictSitemapCategoryListener($queryModifier);

        $listener->restrictQueryBuilder($event);
    }
}

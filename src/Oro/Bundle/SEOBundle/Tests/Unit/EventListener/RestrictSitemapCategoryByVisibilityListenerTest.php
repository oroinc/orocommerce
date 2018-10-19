<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapCategoryByVisibilityListener;
use Oro\Bundle\VisibilityBundle\Model\CategoryVisibilityQueryBuilderModifier;

class RestrictSitemapCategoryByVisibilityListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testFilterHiddenForAnonymousCategories()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);
        /** @var CategoryVisibilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject $queryModifier */
        $queryModifier = $this->createMock(CategoryVisibilityQueryBuilderModifier::class);
        $queryModifier->expects($this->once())
            ->method('restrictForAnonymous')
            ->with($queryBuilder);
        $version = 1;
        $event = new RestrictSitemapEntitiesEvent($queryBuilder, $version);
        $listener = new RestrictSitemapCategoryByVisibilityListener($queryModifier);

        $listener->restrictQueryBuilder($event);
    }
}

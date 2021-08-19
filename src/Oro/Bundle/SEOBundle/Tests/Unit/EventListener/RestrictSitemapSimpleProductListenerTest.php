<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapSimpleProductListener;
use PHPUnit\Framework\MockObject\MockObject;

class RestrictSitemapSimpleProductListenerTest extends \PHPUnit\Framework\TestCase
{
    private FeatureChecker|MockObject $featureChecker;
    private QueryBuilderModifierInterface|MockObject $dbQueryBuilderModifier;
    private RestrictSitemapSimpleProductListener $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->dbQueryBuilderModifier = $this->createMock(QueryBuilderModifierInterface::class);

        $this->listener = new RestrictSitemapSimpleProductListener(
            $this->dbQueryBuilderModifier
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('simple_variations_view_restriction');
    }

    public function testRestrictQueryBuilderHideCompletely()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $event = new RestrictSitemapEntitiesEvent($qb, 1);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->dbQueryBuilderModifier->expects($this->once())
            ->method('modify')
            ->with($qb);

        $this->listener->restrictQueryBuilder($event);
    }

    public function testRestrictQueryBuilderNonHideCompletely()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $event = new RestrictSitemapEntitiesEvent($qb, 1);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->dbQueryBuilderModifier->expects($this->never())
            ->method('modify');

        $this->listener->restrictQueryBuilder($event);
    }
}

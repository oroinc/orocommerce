<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapCmsPageByUrlSlugsListener;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestrictSitemapCmsPageByUrlSlugsListenerTest extends TestCase
{
    private RestrictSitemapCmsPageByUrlSlugsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new RestrictSitemapCmsPageByUrlSlugsListener();
    }

    public function testRestrictQueryBuilderJoinsSlugsWhenNotAlreadyJoined(): void
    {
        /** @var QueryBuilder&MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('getDQLPart')
            ->with('join')
            ->willReturn([]);
        $queryBuilder->expects(self::once())
            ->method('innerJoin')
            ->with(sprintf('%s.slugs', UrlItemsProvider::ENTITY_ALIAS), 'slugs');
        $queryBuilder->expects(self::never())
            ->method('andWhere');

        $this->listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($queryBuilder, 1));
    }

    public function testRestrictQueryBuilderAddsConditionWhenSlugsAlreadyJoined(): void
    {
        $join = $this->createMock(Join::class);
        $join->method('getAlias')->willReturn('slugs');

        /** @var QueryBuilder&MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('getDQLPart')
            ->with('join')
            ->willReturn(['entityAlias' => [$join]]);
        $queryBuilder->expects(self::once())
            ->method('expr')
            ->willReturn(new Expr());
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('slugs.id IS NOT NULL');
        $queryBuilder->expects(self::never())
            ->method('innerJoin');

        $this->listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($queryBuilder, 1));
    }

    public function testRestrictQueryBuilderJoinsSlugsWhenOnlyAnotherAliasIsJoined(): void
    {
        $join = $this->createMock(Join::class);
        $join->method('getAlias')->willReturn('other');

        /** @var QueryBuilder&MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('getDQLPart')
            ->with('join')
            ->willReturn(['entityAlias' => [$join]]);
        $queryBuilder->expects(self::once())
            ->method('innerJoin')
            ->with(sprintf('%s.slugs', UrlItemsProvider::ENTITY_ALIAS), 'slugs');
        $queryBuilder->expects(self::never())
            ->method('andWhere');

        $this->listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($queryBuilder, 1));
    }
}

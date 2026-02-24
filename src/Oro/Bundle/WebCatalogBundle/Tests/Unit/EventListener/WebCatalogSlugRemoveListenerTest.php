<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\BufferedQueryResultIteratorStub;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogSlugRemoveListener;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebCatalogSlugRemoveListenerTest extends TestCase
{
    use EntityTrait;

    private ContentVariantRepository|MockObject $contentVariantRepository;

    private SlugRepository|MockObject $slugRepository;

    private WebCatalogSlugRemoveListener|MockObject $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->contentVariantRepository = $this->createMock(ContentVariantRepository::class);
        $this->slugRepository = $this->createMock(SlugRepository::class);

        $this->listener = $this->getMockBuilder(WebCatalogSlugRemoveListener::class)
            ->setConstructorArgs([$this->contentVariantRepository, $this->slugRepository])
            ->onlyMethods(['createIterator'])
            ->getMock();
    }

    public function testPreRemove(): void
    {
        $webCatalogId = 1;

        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);

        $qb = $this->createMock(QueryBuilder::class);

        $this->contentVariantRepository->expects(self::once())
            ->method('getSlugIdsByWebCatalogQueryBuilder')
            ->with($webCatalogId)
            ->willReturn($qb);

        $iterator = new BufferedQueryResultIteratorStub([['id' => 10], ['id' => 20], ['id' => 30]]);
        $this->listener->expects(self::once())
            ->method('createIterator')
            ->with($qb)
            ->willReturn($iterator);

        $this->slugRepository->expects(self::once())
            ->method('deleteByIds')
            ->with([10, 20, 30]);

        $this->listener->preRemove($webCatalog);
    }

    public function testPreRemoveWithNoSlugs(): void
    {
        $webCatalogId = 2;

        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);

        $qb = $this->createMock(QueryBuilder::class);

        $this->contentVariantRepository->expects(self::once())
            ->method('getSlugIdsByWebCatalogQueryBuilder')
            ->with($webCatalogId)
            ->willReturn($qb);

        $this->listener->expects(self::once())
            ->method('createIterator')
            ->with($qb)
            ->willReturn(new BufferedQueryResultIteratorStub([]));

        $this->slugRepository->expects(self::never())
            ->method('deleteByIds');

        $this->listener->preRemove($webCatalog);
    }
}

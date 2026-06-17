<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Provider\ReindexProductsByAttributesWebsiteResolver;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReindexProductsByAttributesWebsiteResolverTest extends TestCase
{
    private WebsiteRepository|MockObject $repository;

    private ReindexProductsByAttributesWebsiteResolver $resolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WebsiteRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($this->repository);

        $this->resolver = new ReindexProductsByAttributesWebsiteResolver($doctrine);
    }

    public function testGetWebsiteIdsToReindexReturnsAllWebsites(): void
    {
        $this->repository->expects(self::once())
            ->method('getAllWebsitesIds')
            ->willReturn([1, 2, 3]);

        self::assertSame([1, 2, 3], $this->resolver->getWebsiteIdsToReindex([10, 20]));
    }
}

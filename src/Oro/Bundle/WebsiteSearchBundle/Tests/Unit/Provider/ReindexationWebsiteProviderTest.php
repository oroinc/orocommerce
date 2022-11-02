<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProvider;

class ReindexationWebsiteProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ReindexationWebsiteProvider */
    private $websiteProvider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->websiteProvider = new ReindexationWebsiteProvider($this->doctrine);
    }

    public function testGetReindexationWebsiteIds(): void
    {
        $websiteId = 123;
        $website = $this->createMock(Website::class);
        $website->expects(self::once())
            ->method('getId')
            ->willReturn($websiteId);

        self::assertEquals(
            [$websiteId],
            $this->websiteProvider->getReindexationWebsiteIds($website)
        );
    }

    public function testGetReindexationWebsiteIdsForOrganization(): void
    {
        $websiteIds = [123];
        $organization = $this->createMock(Organization::class);

        $repository = $this->createMock(WebsiteRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('getAllWebsitesIds')
            ->with(self::identicalTo($organization))
            ->willReturn($websiteIds);

        self::assertEquals(
            $websiteIds,
            $this->websiteProvider->getReindexationWebsiteIdsForOrganization($organization)
        );
    }
}

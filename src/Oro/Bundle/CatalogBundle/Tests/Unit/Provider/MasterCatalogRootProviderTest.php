<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;

class MasterCatalogRootProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepository;

    /** @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var MasterCatalogRootProvider */
    private $provider;

    public function setUp()
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);

        $this->provider = new MasterCatalogRootProvider(
            $this->categoryRepository,
            $this->tokenAccessor
        );
    }

    public function testGetMasterCatalogRootForCurrentOrganization()
    {
        $organizationFromToken = new Organization();
        $category = new Category();

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organizationFromToken);

        $this->categoryRepository->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->with($organizationFromToken)
            ->willReturn($category);

        $this->provider->getMasterCatalogRootForCurrentOrganization();
    }
}

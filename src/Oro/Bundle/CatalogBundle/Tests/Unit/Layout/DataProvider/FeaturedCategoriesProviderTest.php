<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Layout\DataProvider\FeaturedCategoriesProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class FeaturedCategoriesProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $categoryTreeProvider;

    /**
     * @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenAccessor;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localizationHelper;

    /**
     * @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var FeaturedCategoriesProvider
     */
    protected $featuredCategoriesProvider;

    protected function setUp(): void
    {
        $this->categoryTreeProvider = $this->getMockBuilder(CategoryTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->featuredCategoriesProvider = new FeaturedCategoriesProvider(
            $this->categoryTreeProvider,
            $this->tokenAccessor,
            $this->localizationHelper
        );

        $this->cache = $this->createMock(AbstractAdapter::class);
        $this->featuredCategoriesProvider->setCache($this->cache);
    }

    public function testGetAllCached()
    {
        $result = ['id' => 1, 'title' => '', 'small_image' => null];

        $this->cache->expects($this->once())
            ->method('get')
            ->with('featured_categories__0_0_0_1_7')
            ->willReturn($result);

        $user = new CustomerUser();
        $organization = $this->getEntity(Organization::class, ['id' => 7]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->categoryTreeProvider->expects($this->never())
            ->method('getCategories');

        $this->cache->expects($this->never())
            ->method('save');

        $actual = $this->featuredCategoriesProvider->getAll([1]);
        $this->assertEquals($result, $actual);
    }
}

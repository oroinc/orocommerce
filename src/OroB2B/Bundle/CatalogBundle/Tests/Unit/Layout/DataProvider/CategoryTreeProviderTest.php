<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Layout\DataProvider\CategoryTreeProvider as CategoryTreeDataProvider;
use OroB2B\Bundle\CatalogBundle\Provider\CategoryTreeProvider;

class CategoryTreeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|CategoryTreeProvider */
    protected $categoryTreeProvider;

    /** @var CategoryTreeDataProvider */
    protected $provider;

    /** @var  ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    public function setUp()
    {
        $this->categoryTreeProvider = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Provider\CategoryTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new CategoryTreeDataProvider(
            $this->categoryTreeProvider,
            $this->doctrine
        );
    }

    public function testGetData()
    {
        $childCategory = new Category();
        $childCategory->setLevel(2);

        $mainCategory = new Category();
        $mainCategory->setLevel(1);
        $mainCategory->addChildCategory($childCategory);

        $rootCategory = new Category();
        $rootCategory->setLevel(0);
        $rootCategory->addChildCategory($mainCategory);

        $user = new AccountUser();

        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->atLeastOnce())->method('getRepository')
            ->willReturn($repo);

        $repo
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($rootCategory);
        
        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($user, $rootCategory, null)
            ->willReturn([$mainCategory]);

        $context = new LayoutContext();
        $context->set('logged_user', $user);
        $actual = $this->provider->getData($context);

        $this->assertEquals([$mainCategory], $actual);
    }
}

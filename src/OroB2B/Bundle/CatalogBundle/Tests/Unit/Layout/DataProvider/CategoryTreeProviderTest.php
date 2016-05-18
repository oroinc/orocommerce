<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Layout\DataProvider\CategoryTreeProvider as CategoryTreeDataProvider;
use OroB2B\Bundle\CatalogBundle\Provider\CategoryTreeProvider;

class CategoryTreeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|CategoryTreeProvider */
    protected $categoryTreeProvider;

    /** @var CategoryTreeDataProvider */
    protected $provider;

    public function setUp()
    {
        $this->categoryTreeProvider = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Provider\CategoryTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new CategoryTreeDataProvider(
            $this->categoryTreeProvider
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

        $categories = [$rootCategory, $mainCategory, $childCategory];
        $expected = [
            'all' => $categories,
            'main' => [$mainCategory],
        ];

        $user = new AccountUser();

        $this->categoryTreeProvider->expects($this->at(0))
            ->method('getCategories')
            ->with($user, null, null)
            ->willReturn($categories);

        $this->categoryTreeProvider->expects($this->at(1))
            ->method('getCategories')
            ->with($user, $rootCategory, null)
            ->willReturn([$mainCategory]);

        $context = new LayoutContext();
        $context->set('logged_user', $user);
        $actual = $this->provider->getData($context);

        $this->assertEquals($expected, $actual);
    }
}

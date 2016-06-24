<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;
use OroB2B\Bundle\CatalogBundle\Layout\DataProvider\CurrentCategoryProvider;

class CurrentCategoryProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestProductHandler;

    /**
     * @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryRepository;

    /**
     * @var CurrentCategoryProvider
     */
    protected $currentCategoryProvider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->requestProductHandler = $this
            ->getMockBuilder('OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepository = $this
            ->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->currentCategoryProvider = new CurrentCategoryProvider(
            $this->requestProductHandler,
            $this->categoryRepository
        );
    }

    public function testGetDataUsingMasterCatalogRoot()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $category = new Category();

        $this
            ->requestProductHandler
            ->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(null);

        $this
            ->categoryRepository
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($category);

        $result = $this->currentCategoryProvider->getData($context);
        $this->assertSame($category, $result);
    }

    public function testGetDataUsingFind()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $category = new Category();
        $categoryId = 1;

        $this
            ->requestProductHandler
            ->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this
            ->categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $result = $this->currentCategoryProvider->getData($context);
        $this->assertSame($category, $result);
    }
}

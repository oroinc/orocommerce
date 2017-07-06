<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\CatalogBundle\Api\Processor\LoadCategoryForProduct;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Component\ChainProcessor\ContextInterface;

class LoadCategoryForProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ValueNormalizer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $valueNormalizer;

    /**
     * @var LoadCategoryForProduct
     */
    protected $loadCategoryForProduct;

    /**
     * @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repo;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer = $this->getMockBuilder(ValueNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loadCategoryForProduct = new LoadCategoryForProduct($this->doctrineHelper, $this->valueNormalizer);
        $this->repo = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);
    }

    public function testProcess()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(ContextInterface::class);
        $productResult = $this->loadProduckMockJson();
        $expectedResult = $productResult;
        $expectedResult['data']['relationships']['category'] = [
            'data' =>
                [
                    'id' => '1',
                    'type' => 'categories',
                ],
        ];
        $context->expects($this->once())
            ->method('getResult')
            ->willReturn($productResult);
        $category = $this->createMock(Category::class);
        $category->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->repo->expects($this->once())
            ->method('findOneByProductSku')
            ->willReturn($category);
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('categories');
        $context->expects($this->once())
            ->method('setResult')
            ->willReturnCallback(
                function ($result) use ($expectedResult) {
                    $this->assertTrue($expectedResult == $result);
                    $this->assertArrayHasKey('data', $result);
                }
            );

        $this->loadCategoryForProduct->process($context);
    }

    public function testProcessGetsIgnoredIfNoSkuSpecified()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(ContextInterface::class);
        $productResult = $this->loadProduckMockJson();
        unset($productResult['data']['attributes']['sku']);
        $context->expects($this->once())
            ->method('getResult')
            ->willReturn($productResult);
        $context->expects($this->never())
            ->method('setResult');
        $this->loadCategoryForProduct->process($context);
    }

    public function testProcessDoesNotMofifyResultIfNoCategoryFound()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(ContextInterface::class);
        $productResult = $this->loadProduckMockJson();
        unset($productResult['data']['relationships']['category']);
        $context->expects($this->once())
            ->method('getResult')
            ->willReturn($productResult);
        $context->expects($this->once())
            ->method('setResult')
            ->willReturnCallback(
                function ($result) use ($productResult) {
                    $this->assertTrue($productResult == $result);
                }
            );

        $this->loadCategoryForProduct->process($context);
    }

    /**
     * @return bool|string
     */
    protected function loadProduckMockJson()
    {
        return json_decode(file_get_contents(__DIR__ . '/product_mock.json'), true);
    }
}

<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\CatalogBundle\Api\Processor\LoadCategoryForProduct;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

class LoadCategoryForProductTest extends GetProcessorTestCase
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
    protected $processor;

    /**
     * @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->repo = $this->createMock(CategoryRepository::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);

        $this->processor = new LoadCategoryForProduct($this->doctrineHelper, $this->valueNormalizer);
    }

    public function testProcess()
    {
        $productResult = $this->loadProductMockJson();
        $expectedResult = $productResult;
        $expectedResult['data']['relationships']['category'] = [
            'data' =>
                [
                    'id' => '1',
                    'type' => 'categories',
                ],
        ];

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

        $this->context->setResult($productResult);
        $this->processor->process($this->context);

        self::assertEquals($expectedResult, $this->context->getResult());
    }

    public function testProcessGetsIgnoredIfNoSkuSpecified()
    {
        $productResult = $this->loadProductMockJson();
        unset($productResult['data']['attributes']['sku']);

        $this->context->setResult($productResult);
        $this->processor->process($this->context);

        self::assertEquals($productResult, $this->context->getResult());
    }

    public function testProcessDoesNotModifyResultIfNoCategoryFound()
    {
        $productResult = $this->loadProductMockJson();
        unset($productResult['data']['relationships']['category']);

        $this->context->setResult($productResult);
        $this->processor->process($this->context);

        self::assertEquals($productResult, $this->context->getResult());
    }

    /**
     * @return array
     */
    protected function loadProductMockJson()
    {
        return json_decode(file_get_contents(__DIR__ . '/product_mock.json'), true);
    }
}

<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\CatalogBundle\Api\Processor\LoadCategoryForProductCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

class LoadCategoryForProductCollectionTest extends GetListProcessorTestCase
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
     * @var LoadCategoryForProductCollection
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

        $this->processor = new LoadCategoryForProductCollection(
            $this->doctrineHelper,
            $this->valueNormalizer
        );
    }

    public function testProcess()
    {
        $resultInput = $this->loadProductMockJson();
        $expectedResult = $resultInput;
        foreach ($resultInput['data'] as &$product) {
            unset($product['relationships']['category']);
        }

        $index = 1;
        $this->repo->expects($this->exactly(5))
            ->method('findOneByProductSku')
            ->willReturnCallback(
                function () use (&$index) {
                    $categ = $this->createMock(Category::class);
                    $categ->expects($this->once())
                        ->method('getId')
                        ->willReturn($index++);

                    return $categ;
                }
            );
        $this->valueNormalizer->expects($this->exactly(5))
            ->method('normalizeValue')
            ->willReturn('categories');

        $this->context->setResult($resultInput);
        $this->processor->process($this->context);

        self::assertEquals($expectedResult, $this->context->getResult());
    }

    /**
     * @return array
     */
    protected function loadProductMockJson()
    {
        return json_decode(file_get_contents(__DIR__ . '/product_collection_mock.json'), true);
    }
}

<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\CatalogBundle\Api\Processor\LoadCategoryForProductCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Component\ChainProcessor\ContextInterface;

class LoadCategoryForProductCollectionTest extends \PHPUnit_Framework_TestCase
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
    protected $loadCategoryForProductCollection;

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
        $this->repo = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);
        $this->loadCategoryForProductCollection = new LoadCategoryForProductCollection(
            $this->doctrineHelper,
            $this->valueNormalizer
        );
    }

    public function testProcess()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(ContextInterface::class);
        $resultInput = $this->loadProduckMockJson();
        $expectedResult = $resultInput;
        foreach ($resultInput['data'] as &$product) {
            unset($product['relationships']['category']);
        }
        $context->expects($this->once())
            ->method('getResult')
            ->willReturn($resultInput);
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

        $context->expects($this->once())
            ->method('setResult')
            ->willReturnCallback(
                function ($result) use ($expectedResult) {
                    $this->assertTrue($result === $expectedResult);
                }
            );
        $this->loadCategoryForProductCollection->process($context);
    }

    /**
     * @return bool|string
     */
    protected function loadProduckMockJson()
    {
        return json_decode(file_get_contents(__DIR__ . '/product_collection_mock.json'), true);
    }
}

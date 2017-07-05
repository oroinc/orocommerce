<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\CatalogBundle\Api\Processor\RemoveCategoryFromProductRequest;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;

class RemoveCategoryFromProductRequestTest extends \PHPUnit_Framework_TestCase
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
     * @var RemoveCategoryFromProductRequest
     */
    protected $removeCategoryFromProductRequest;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject|
     */
    protected $em;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repo;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->valueNormalizer = $this->getMockBuilder(ValueNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->removeCategoryFromProductRequest = new RemoveCategoryFromProductRequest(
            $this->doctrineHelper,
            $this->valueNormalizer
        );
    }

    public function testProcessShouldBeIgnored()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(CreateContext::class);
        $context->expects($this->once())
            ->method('getRequestData');
        $context->expects($this->never())
            ->method('setRequestData');

        $this->removeCategoryFromProductRequest->process($context);
    }

    public function testProcessShouldBeIgnoredIfNoCategoryDefined()
    {
        $context = $this->createMock(CreateContext::class);

        $productRequest = $this->loadProduckMockJson();
        unset($productRequest['data']['relationships']['category']);

        $context->expects($this->once())
            ->method('getRequestData')
            ->willReturn($productRequest);
        $context->expects($this->never())
            ->method('setRequestData');

        $this->removeCategoryFromProductRequest->process($context);
    }

    public function testProcessShouldSetCategoryToContextAndRemoveFromRequest()
    {
        $context = $this->createMock(CreateContext::class);

        $productRequest = $this->loadProduckMockJson();
        $category = new Category();
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($category);

        $context->expects($this->once())
            ->method('getRequestData')
            ->willReturn($productRequest);
        $modifiedProductRequest = $productRequest;
        unset($modifiedProductRequest['data']['relationships']['category']);
        $context->expects($this->once())
            ->method('setRequestData')
            ->with($modifiedProductRequest);
        $context->expects($this->once())
            ->method('set')
            ->with('category', $category);
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('categories');

        $this->removeCategoryFromProductRequest->process($context);
    }

    public function testProcessGeneratesErrorIfCategoryValidationFailed()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(CreateContext::class);
        $context->expects($this->never())
            ->method('setRequestData');
        $productRequest = $this->loadProduckMockJson();
        unset($productRequest['data']['relationships']['category']['data']);
        $context->expects($this->once())
            ->method('getRequestData')
            ->willReturn($productRequest);
        $context->expects($this->once())
            ->method('addError')
            ->willReturnCallback(
                function ($error) {
                    $this->assertInstanceOf(Error::class, $error);
                    $this->assertEquals('Category definition must have a \'data\' key', $error->getDetail());
                }
            );

        $this->removeCategoryFromProductRequest->process($context);
    }

    /**
     * @dataProvider getCategoryValidationProvider
     */
    public function testProcessGeneratesErrorIfCategorInfoValidationFailed($categoryInfo)
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(CreateContext::class);
        $context->expects($this->never())
            ->method('setRequestData');
        $productRequest = $this->loadProduckMockJson();
        $productRequest['data']['relationships']['category']['data'] = $categoryInfo;
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('categories');
        $context->expects($this->once())
            ->method('getRequestData')
            ->willReturn($productRequest);
        $context->expects($this->once())
            ->method('addError')
            ->willReturnCallback(
                function ($error) {
                    $this->assertInstanceOf(Error::class, $error);
                    $this->assertEquals('Category definition must have a valid id and type', $error->getDetail());
                }
            );

        $this->removeCategoryFromProductRequest->process($context);
    }

    /**
     * @return array
     */
    public function getCategoryValidationProvider()
    {
        return [
            [['type' => 'notcategories', 'id' => 1]],
            [['type' => 'categories']],
            [['id' => 1]],
            [[]],
        ];
    }

    public function testProcessGeneratesErrorIfCategoryNotFound()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(CreateContext::class);
        $context->expects($this->never())
            ->method('setRequestData');
        $productRequest = $this->loadProduckMockJson();
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('categories');
        $context->expects($this->once())
            ->method('getRequestData')
            ->willReturn($productRequest);
        $context->expects($this->once())
            ->method('addError')
            ->willReturnCallback(
                function ($error) {
                    $this->assertInstanceOf(Error::class, $error);
                    $this->assertEquals('Category id 1 is not valid', $error->getDetail());
                }
            );

        $this->removeCategoryFromProductRequest->process($context);
    }

    /**
     * @return bool|string
     */
    protected function loadProduckMockJson()
    {
        return json_decode(file_get_contents(__DIR__ . '/product_mock.json'), true);
    }
}

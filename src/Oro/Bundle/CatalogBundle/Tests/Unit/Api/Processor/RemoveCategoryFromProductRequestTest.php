<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\CatalogBundle\Api\Processor\RemoveCategoryFromProductRequest;
use Oro\Bundle\CatalogBundle\Entity\Category;

class RemoveCategoryFromProductRequestTest extends FormProcessorTestCase
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
    protected $processor;

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
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->processor = new RemoveCategoryFromProductRequest(
            $this->doctrineHelper,
            $this->valueNormalizer
        );
    }

    public function testProcessShouldBeIgnored()
    {
        $productRequest = [];

        $this->context->setRequestData($productRequest);
        $this->processor->process($this->context);

        self::assertEquals($productRequest, $this->context->getRequestData());
    }

    public function testProcessShouldBeIgnoredIfNoCategoryDefined()
    {
        $productRequest = $this->loadProductMockJson();
        unset($productRequest['data']['relationships']['category']);

        $this->context->setRequestData($productRequest);
        $this->processor->process($this->context);

        self::assertEquals($productRequest, $this->context->getRequestData());
    }

    public function testProcessShouldSetCategoryToContextAndRemoveFromRequest()
    {
        $productRequest = $this->loadProductMockJson();
        $modifiedProductRequest = $productRequest;
        unset($modifiedProductRequest['data']['relationships']['category']);

        $category = new Category();
        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($category);

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('categories');

        $this->context->setRequestData($productRequest);
        $this->processor->process($this->context);

        self::assertSame($category, $this->context->get('category'));
        self::assertEquals($modifiedProductRequest, $this->context->getRequestData());
    }

    public function testProcessGeneratesErrorIfCategoryValidationFailed()
    {
        $productRequest = $this->loadProductMockJson();
        unset($productRequest['data']['relationships']['category']['data']);

        $this->context->setRequestData($productRequest);
        $this->processor->process($this->context);

        self::assertEquals($productRequest, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::REQUEST_DATA,
                    'Category definition must have a \'data\' key'
                )->setSource(ErrorSource::createByPointer('/data/relationships/category'))
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @dataProvider getCategoryValidationProvider
     */
    public function testProcessGeneratesErrorIfCategorInfoValidationFailed($categoryInfo)
    {
        $productRequest = $this->loadProductMockJson();
        $productRequest['data']['relationships']['category']['data'] = $categoryInfo;

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('categories');

        $this->context->setRequestData($productRequest);
        $this->processor->process($this->context);

        self::assertEquals($productRequest, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::REQUEST_DATA,
                    'Category definition must have a valid id and type'
                )->setSource(ErrorSource::createByPointer('/data/relationships/category/data'))
            ],
            $this->context->getErrors()
        );
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
        $productRequest = $this->loadProductMockJson();

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('categories');

        $this->context->setRequestData($productRequest);
        $this->processor->process($this->context);

        self::assertEquals($productRequest, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::REQUEST_DATA,
                    'Category id 1 is not valid'
                )->setSource(ErrorSource::createByPointer('/data/relationships/category/data/id'))
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @return array
     */
    protected function loadProductMockJson()
    {
        return json_decode(file_get_contents(__DIR__ . '/product_mock.json'), true);
    }
}

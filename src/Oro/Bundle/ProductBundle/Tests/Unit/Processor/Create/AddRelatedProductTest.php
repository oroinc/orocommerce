<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\Processor\Create\AddRelatedProduct;
use Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\FakeAssignerStrategy;

class AddRelatedProductTest extends \PHPUnit_Framework_TestCase
{
    /** @var RelatedProductRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $relatedProductsRepository;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var MetadataProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $metadataProvider;

    /** @var FakeAssignerStrategy */
    private $assignerStrategy;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $configProvider;

    /** @var AddRelatedProduct */
    private $processor;

    protected function setUp()
    {
        $this->relatedProductsRepository = $this->getMockBuilder(RelatedProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->with(RelatedProduct::class)
            ->willReturn($this->relatedProductsRepository);

        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder(MetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assignerStrategy = new FakeAssignerStrategy();
        $this->processor = new AddRelatedProduct($this->assignerStrategy, $this->doctrineHelper);
    }

    public function testRelatedProductIsAddedWhenThereIsNoValidationError()
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn(new RelatedProduct());

        $context = $this->prepareContext();

        $this->assertFalse($context->hasErrors());
        $this->processor->process($context);
        $this->assertFalse($context->hasErrors());
    }

    public function testErrorIsAddedToContextWhenRelatedItemsFunctionalityIsDisabled()
    {
        $this->assignerStrategy->functionalityEnabled = false;
        $context = $this->prepareContext();

        $this->assertFalse($context->hasErrors());
        $this->processor->process($context);
        $this->assertCount(1, $context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddRelatedProductToItself()
    {
        $this->assignerStrategy->addRelatedProductToItself = true;
        $context = $this->prepareContext();

        $this->assertFalse($context->hasErrors());
        $this->processor->process($context);
        $this->assertCount(1, $context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddMoreProductsThanLimitAllows()
    {
        $this->assignerStrategy->exceedLimit = true;
        $context = $this->prepareContext();

        $this->assertFalse($context->hasErrors());
        $this->processor->process($context);
        $this->assertCount(1, $context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddAlreadyExistingRelation()
    {
        $this->relationExistsInDatabase();
        $context = $this->prepareContext();

        $this->assertFalse($context->hasErrors());
        $this->processor->process($context);

        $errors = $context->getErrors();
        $this->assertCount(1, $errors);
        /** @var Error $error */
        $error = reset($errors);
        $expectedErrorMessage = 'oro.product.related_items.related_product.relation_already_exists';
        $this->assertSame($expectedErrorMessage, (string) $error->getDetail()->getName());
    }

    /**
     * @return CreateContext
     */
    private function prepareContext()
    {
        $context = new CreateContext($this->configProvider, $this->metadataProvider);

        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct(new Product())->setRelatedProduct(new Product());
        $context->setResult($relatedProduct);

        return $context;
    }

    private function relationExistsInDatabase()
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('exists')
            ->willReturn(true);
    }
}

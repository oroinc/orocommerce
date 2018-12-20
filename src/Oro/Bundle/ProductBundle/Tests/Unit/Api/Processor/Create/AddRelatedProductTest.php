<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Create;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\CreateProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Api\Processor\Create\AddRelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\FakeAssignerStrategy;

class AddRelatedProductTest extends CreateProcessorTestCase
{
    /** @var RelatedProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedProductsRepository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FakeAssignerStrategy */
    private $assignerStrategy;

    /** @var AddRelatedProduct */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->relatedProductsRepository = $this->createMock(RelatedProductRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->assignerStrategy = new FakeAssignerStrategy();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(RelatedProduct::class)
            ->willReturn($this->relatedProductsRepository);

        $this->processor = new AddRelatedProduct($this->assignerStrategy, $this->doctrineHelper);
    }

    public function testRelatedProductIsAddedWhenThereIsNoValidationError()
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn(new RelatedProduct());

        $this->prepareContext();
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testErrorIsAddedToContextWhenRelatedItemsFunctionalityIsDisabled()
    {
        $this->assignerStrategy->functionalityEnabled = false;

        $this->prepareContext();
        $this->processor->process($this->context);

        $this->assertCount(1, $this->context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddRelatedProductToItself()
    {
        $this->assignerStrategy->addRelatedProductToItself = true;

        $this->prepareContext();
        $this->processor->process($this->context);

        $this->assertCount(1, $this->context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddMoreProductsThanLimitAllows()
    {
        $this->assignerStrategy->exceedLimit = true;

        $this->prepareContext();
        $this->processor->process($this->context);

        $this->assertCount(1, $this->context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddAlreadyExistingRelation()
    {
        $this->relationExistsInDatabase();

        $this->prepareContext();
        $this->processor->process($this->context);

        $errors = $this->context->getErrors();
        $this->assertCount(1, $errors);
        /** @var Error $error */
        $error = reset($errors);
        $expectedErrorMessage = 'oro.product.related_items.related_product.relation_already_exists';
        $this->assertSame($expectedErrorMessage, (string) $error->getDetail()->getName());
    }

    private function prepareContext()
    {
        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct(new Product())->setRelatedItem(new Product());
        $this->context->setResult($relatedProduct);
    }

    private function relationExistsInDatabase()
    {
        $this->relatedProductsRepository->expects($this->any())
            ->method('exists')
            ->willReturn(true);
    }
}

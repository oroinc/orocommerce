<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\CreateProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Api\Processor\SaveRelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface;

class SaveRelatedProductTest extends CreateProcessorTestCase
{
    /** @var RelatedProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedProductsRepository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AssignerStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $assignerStrategy;

    /** @var SaveRelatedProduct */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->relatedProductsRepository = $this->createMock(RelatedProductRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->assignerStrategy = $this->createMock(AssignerStrategyInterface::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(RelatedProduct::class)
            ->willReturn($this->relatedProductsRepository);

        $this->processor = new SaveRelatedProduct($this->assignerStrategy, $this->doctrineHelper);
    }

    public function testRelatedProductIsAddedWhenThereIsNoValidationError()
    {
        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct(new Product())->setRelatedItem(new Product());

        $this->relatedProductsRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(new RelatedProduct());
        $this->relatedProductsRepository->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->assignerStrategy->expects($this->once())
            ->method('addRelations');

        $this->context->setResult($relatedProduct);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testErrorIsAddedToContextWhenRelatedItemsFunctionalityIsDisabled()
    {
        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct(new Product())->setRelatedItem(new Product());

        $this->relatedProductsRepository->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->assignerStrategy->expects($this->once())
            ->method('addRelations')
            ->willThrowException(new \LogicException('Related Items functionality is disabled.'));

        $this->context->setResult($relatedProduct);
        $this->processor->process($this->context);

        $this->assertCount(1, $this->context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddRelatedProductToItself()
    {
        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct(new Product())->setRelatedItem(new Product());

        $this->relatedProductsRepository->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->assignerStrategy->expects($this->once())
            ->method('addRelations')
            ->willThrowException(new \InvalidArgumentException(
                'It is not possible to create relations from product to itself.'
            ));

        $this->context->setResult($relatedProduct);
        $this->processor->process($this->context);

        $this->assertCount(1, $this->context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddMoreProductsThanLimitAllows()
    {
        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct(new Product())->setRelatedItem(new Product());

        $this->relatedProductsRepository->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->assignerStrategy->expects($this->once())
            ->method('addRelations')
            ->willThrowException(new \OverflowException(
                'It is not possible to add more related items, because of the limit of relations.'
            ));

        $this->context->setResult($relatedProduct);
        $this->processor->process($this->context);

        $this->assertCount(1, $this->context->getErrors());
    }

    public function testErrorIsAddedToContextWhenUserTriesToAddAlreadyExistingRelation()
    {
        $relatedProduct = new RelatedProduct();
        $relatedProduct->setProduct(new Product())->setRelatedItem(new Product());

        $this->relatedProductsRepository->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->assignerStrategy->expects($this->never())
            ->method('addRelations');

        $this->context->setResult($relatedProduct);
        $this->processor->process($this->context);

        $errors = $this->context->getErrors();
        $this->assertCount(1, $errors);
        /** @var Error $error */
        $error = reset($errors);
        $expectedErrorMessage = 'oro.product.related_items.related_product.relation_already_exists';
        $this->assertSame($expectedErrorMessage, (string)$error->getDetail()->getName());
    }
}

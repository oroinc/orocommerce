<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Exception\AssignerNotFoundException;
use Oro\Bundle\ProductBundle\Form\Handler\RelatedItemsHandler;
use Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Contracts\Translation\TranslatorInterface;

class RelatedItemsHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RelatedItemsHandler */
    private $handler;

    /** @var AssignerStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedAssigner;

    /** @var AssignerStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $upsellAssigner;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->relatedAssigner = $this->createMock(AssignerStrategyInterface::class);
        $this->upsellAssigner = $this->createMock(AssignerStrategyInterface::class);

        $this->handler = new RelatedItemsHandler($this->translator);
    }

    public function testProcessRelatedProducts()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productToAdd = $this->getEntity(Product::class, ['id' => 2]);
        $productToRemove = $this->getEntity(Product::class, ['id' => 3]);
        $appendField = $this->getField([$productToAdd]);
        $removeField = $this->getField([$productToRemove]);

        $this->relatedAssignerShouldAddProducts($product, [$productToAdd]);
        $this->relatedAssignerShouldRemoveProducts($product, [$productToRemove]);
        $this->handler->addAssigner(RelatedItemsHandler::RELATED_PRODUCTS, $this->relatedAssigner);

        $this->assertTrue(
            $this->handler->process(RelatedItemsHandler::RELATED_PRODUCTS, $product, $appendField, $removeField)
        );
    }

    public function testProcessAssignerNotFound()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $appendField = $this->getField();
        $removeField = $this->getField();
        $this->handler->addAssigner(RelatedItemsHandler::RELATED_PRODUCTS, $this->relatedAssigner);

        $this->expectException(AssignerNotFoundException::class);

        $this->handler->process('wrong_name', $product, $appendField, $removeField);
    }

    /**
     * @dataProvider exceptionsProvider()
     */
    public function testProcessErrorOnAddingProductToItself(\Exception $exception)
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $appendField = $this->getField([$product]);
        $appendField->expects($this->once())
            ->method('addError')
            ->with($this->isInstanceOf(FormError::class));
        $removeField = $this->getField();

        $this->assignerShouldThrowException($exception);
        $this->handler->addAssigner(RelatedItemsHandler::RELATED_PRODUCTS, $this->relatedAssigner);

        $this->assertFalse(
            $this->handler->process(RelatedItemsHandler::RELATED_PRODUCTS, $product, $appendField, $removeField)
        );
    }

    public function testHandlerWithTwoAssigners()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productToAdd = $this->getEntity(Product::class, ['id' => 2]);
        $productToRemove = $this->getEntity(Product::class, ['id' => 3]);
        $appendField = $this->getField([$productToAdd]);
        $removeField = $this->getField([$productToRemove]);

        $this->relatedAssignerShouldAddProducts($product, [$productToAdd]);
        $this->relatedAssignerShouldRemoveProducts($product, [$productToRemove]);

        $this->upsellAssignerShouldAddProducts($product, [$productToAdd]);
        $this->upsellAssignerShouldRemoveProducts($product, [$productToRemove]);

        $this->handler->addAssigner(RelatedItemsHandler::RELATED_PRODUCTS, $this->relatedAssigner);
        $this->handler->addAssigner(RelatedItemsHandler::UPSELL_PRODUCTS, $this->upsellAssigner);

        $this->assertTrue(
            $this->handler->process(RelatedItemsHandler::RELATED_PRODUCTS, $product, $appendField, $removeField)
        );
        $this->assertTrue(
            $this->handler->process(RelatedItemsHandler::UPSELL_PRODUCTS, $product, $appendField, $removeField)
        );
    }

    /**
     * @return array
     */
    public function exceptionsProvider()
    {
        return [
            [new \InvalidArgumentException()],
            [new \LogicException()],
            [new \OverflowException()],
        ];
    }

    /**
     * @param Product[] $data
     * @return \PHPUnit\Framework\MockObject\MockObject|Form
     */
    private function getField($data = [])
    {
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        return $form;
    }

    /**
     * @param Product $product
     * @param Product[] $productsToAdd
     */
    private function relatedAssignerShouldAddProducts(Product $product, array $productsToAdd)
    {
        $this->relatedAssigner->expects($this->once())
            ->method('addRelations')
            ->with($product, $productsToAdd);
    }

    /**
     * @param Product $product
     * @param Product[] $productsToRemove
     */
    private function relatedAssignerShouldRemoveProducts(Product $product, array $productsToRemove)
    {
        $this->relatedAssigner->expects($this->once())
            ->method('removeRelations')
            ->with($product, $productsToRemove);
    }

    /**
     * @param Product $product
     * @param Product[] $productsToAdd
     */
    private function upsellAssignerShouldAddProducts(Product $product, array $productsToAdd)
    {
        $this->upsellAssigner->expects($this->once())
            ->method('addRelations')
            ->with($product, $productsToAdd);
    }

    /**
     * @param Product $product
     * @param Product[] $productsToRemove
     */
    private function upsellAssignerShouldRemoveProducts(Product $product, array $productsToRemove)
    {
        $this->upsellAssigner->expects($this->once())
            ->method('removeRelations')
            ->with($product, $productsToRemove);
    }

    private function assignerShouldThrowException(\Exception $exception)
    {
        $this->relatedAssigner->expects($this->once())
            ->method('addRelations')
            ->willThrowException($exception);
    }
}

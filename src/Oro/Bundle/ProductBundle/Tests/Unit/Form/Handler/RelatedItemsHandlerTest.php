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

    /** @var AssignerStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedAssigner;

    /** @var AssignerStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $upsellAssigner;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var RelatedItemsHandler */
    private $handler;

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
     * @dataProvider exceptionsProvider
     */
    public function testProcessErrorOnAddingProductToItself(\Exception $exception)
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $appendField = $this->getField([$product]);
        $appendField->expects($this->once())
            ->method('addError')
            ->with($this->isInstanceOf(FormError::class));
        $removeField = $this->getField();

        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn ($value) =>  $value . '_translated');

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

    public function exceptionsProvider(): array
    {
        return [
            [new \InvalidArgumentException()],
            [new \LogicException()],
            [new \OverflowException()],
        ];
    }

    private function getField(array $data = []): Form|\PHPUnit\Framework\MockObject\MockObject
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        return $form;
    }

    private function relatedAssignerShouldAddProducts(Product $product, array $productsToAdd): void
    {
        $this->relatedAssigner->expects($this->once())
            ->method('addRelations')
            ->with($product, $productsToAdd);
    }

    private function relatedAssignerShouldRemoveProducts(Product $product, array $productsToRemove): void
    {
        $this->relatedAssigner->expects($this->once())
            ->method('removeRelations')
            ->with($product, $productsToRemove);
    }

    private function upsellAssignerShouldAddProducts(Product $product, array $productsToAdd): void
    {
        $this->upsellAssigner->expects($this->once())
            ->method('addRelations')
            ->with($product, $productsToAdd);
    }

    private function upsellAssignerShouldRemoveProducts(Product $product, array $productsToRemove): void
    {
        $this->upsellAssigner->expects($this->once())
            ->method('removeRelations')
            ->with($product, $productsToRemove);
    }

    private function assignerShouldThrowException(\Exception $exception): void
    {
        $this->relatedAssigner->expects($this->once())
            ->method('addRelations')
            ->willThrowException($exception);
    }
}

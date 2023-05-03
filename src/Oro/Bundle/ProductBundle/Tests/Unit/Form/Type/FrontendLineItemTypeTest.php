<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    private const UNITS = ['item', 'kg'];

    private FrontendLineItemType $type;

    protected function setUp(): void
    {
        $this->type = new FrontendLineItemType();

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub(
                        $this->prepareProductUnitSelectionChoices()
                    ),
                    $this->getQuantityType(),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $lineItem = (new ProductLineItem(1))
            ->setProduct($this->getProductEntityWithPrecision(1, 'kg', 3));

        $form = $this->factory->create(FrontendLineItemType::class, $lineItem);

        $this->assertTrue($form->has('quantity'));
        $this->assertTrue($form->has('unit'));
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(['add_product'], $resolvedOptions['validation_groups']);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(ProductLineItem $defaultData, array $submittedData, ProductLineItem $expectedData)
    {
        $form = $this->factory->create(FrontendLineItemType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $product = $this->getProductEntityWithPrecision(1, 'kg', 3);

        $defaultLineItem = new ProductLineItem(1);
        $defaultLineItem->setProduct($product);

        $expectedLineItem = clone $defaultLineItem;
        $expectedLineItem
            ->setQuantity(15.112)
            ->setUnit($product->getUnitPrecision('kg')->getUnit());

        return [
            'New line item with existing shopping list' => [
                'defaultData'   => $defaultLineItem,
                'submittedData' => [
                    'quantity' => 15.112,
                    'unit'     => 'kg'
                ],
                'expectedData'  => $expectedLineItem
            ],
        ];
    }

    private function getProductEntityWithPrecision(int $productId, string $unitCode, int $precision = 0): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $productId);

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setPrecision($precision)
            ->setUnit($unit)
            ->setProduct($product);

        return $product->addUnitPrecision($unitPrecision);
    }

    private function prepareProductUnitSelectionChoices(): array
    {
        $choices = [];
        foreach (self::UNITS as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }
}

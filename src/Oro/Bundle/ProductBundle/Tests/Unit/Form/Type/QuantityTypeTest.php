<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\QuantityParentTypeStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class QuantityTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    private QuantityType $formType;
    private QuantityParentTypeStub $parentType;

    protected function setUp(): void
    {
        $this->formType = $this->getQuantityType();
        $this->parentType = new QuantityParentTypeStub();

        parent::setUp();
    }

    /**
     * @dataProvider defaultDataProvider
     */
    public function testSetDefaultData(
        array $options,
        mixed $expectedBefore,
        mixed $submittedData,
        mixed $expectedAfter
    ) {
        $form = $this->factory->create(QuantityType::class, null, $options);
        $this->assertEquals($expectedBefore, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedAfter, $form->getData());
    }

    public function defaultDataProvider(): array
    {
        return [
            'submit empty should leave default' => [['default_data' => '42'], 42, null, 42],
            'submitted value should override default' => [['default_data' => '42'], 42, 1, 1],
            'submit value without default' => [['default_data' => null], null, 1, 1],
            'submit empty without default' => [['default_data' => null], null, null, null],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $productUnit1 = new ProductUnit();
        $productUnit1->setCode('kg');
        $productUnitPrecision1 = new ProductUnitPrecision();
        $productUnitPrecision1->setUnit($productUnit1);
        $productUnitPrecision1->setPrecision(3);
        $product1 = new Product();
        ReflectionUtil::setId($product1, 1);
        $product1->addUnitPrecision($productUnitPrecision1);

        $product2 = new Product();
        ReflectionUtil::setId($product2, 2);

        $product3 = new Product();
        ReflectionUtil::setId($product3, 1);

        $productUnit2 = new ProductUnit();
        $productUnit2->setCode('kg');

        $productUnit3 = new ProductUnit();
        $productUnit3->setCode('item');
        $productUnit3->setDefaultPrecision(5);

        return [
            new PreloadedExtension([
                $this->parentType,
                EntityType::class => new EntityTypeStub([
                    1 => $product1,
                    2 => $product2,
                    3 => $product3,
                    'kg' => $productUnit2,
                    'item' => $productUnit3
                ]),
                $this->getQuantityType()
            ], []),
        ];
    }
}

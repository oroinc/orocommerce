<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitChoiceType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

class ProductUnitChoiceTypeTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroProductBundle/Tests/Functional/Form/Type/DataFixtures/ProductUnitChoiceType.yml',
        ]);
    }

    /**
     * @dataProvider compactDataProvider
     */
    public function testCreateWhenNoProductShouldHaveAllProductUnits(bool $compact): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(ProductUnitChoiceType::class, null, ['compact' => $compact]);

        self::assertArrayIntersectEquals(
            [
                'class' => ProductUnit::class,
                'compact' => $compact,
                'product' => null,
                'sell' => null,
                'choices' => null,
            ],
            $form->getConfig()->getOptions()
        );

        $formView = $form->createView();

        self::assertContains('oro_product_unit_select', $formView->vars['block_prefixes']);

        /** @var ProductUnit[] $productUnits */
        $productUnits = self::getContainer()->get('doctrine')->getRepository(ProductUnit::class)->findAll();
        self::assertCount(count($productUnits), $formView->vars['choices']);

        /** @var UnitLabelFormatterInterface $unitLabelFormatter */
        $unitLabelFormatter = self::getContainer()->get('oro_product.formatter.product_unit_label');

        /** @var ChoiceView $choiceView */
        foreach ($formView->vars['choices'] as $choiceView) {
            $key = array_search($choiceView->data, $productUnits, true);
            self::assertIsInt($key);

            self::assertEquals($productUnits[$key]->getCode(), $choiceView->value);
            self::assertEquals(
                $unitLabelFormatter->format($productUnits[$key]->getCode(), $compact),
                $choiceView->label
            );
        }
    }

    public function compactDataProvider(): array
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider compactDataProvider
     */
    public function testCreateWhenHasProductShouldHaveOnlyRelatedProductUnits(bool $compact): void
    {
        /** @var ProductUnit $unitItemPrimary */
        $unitItemPrimary = $this->getReference('item');
        /** @var ProductUnit $unitEach */
        $unitEach = $this->getReference('each');
        /** @var ProductUnit $unitKg */
        $unitKg = $this->getReference('kg');
        /** @var ProductUnit $unitSetDisabled */
        $unitSetDisabled = $this->getReference('set');

        $product = (new Product())
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($unitItemPrimary))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitEach))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitKg))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitSetDisabled)->setSell(false));

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            ProductUnitChoiceType::class,
            null,
            ['product' => $product, 'compact' => $compact]
        );

        $productUnits = [
            $unitItemPrimary,
            $unitEach,
            $unitKg,
            $unitSetDisabled,
        ];
        self::assertArrayIntersectEquals(
            [
                'class' => ProductUnit::class,
                'compact' => $compact,
                'product' => $product,
                'sell' => null,
                'choices' => $productUnits,
            ],
            $form->getConfig()->getOptions()
        );

        $formView = $form->createView();

        self::assertContains('oro_product_unit_select', $formView->vars['block_prefixes']);

        self::assertCount(count($productUnits), $formView->vars['choices']);

        /** @var UnitLabelFormatterInterface $unitLabelFormatter */
        $unitLabelFormatter = self::getContainer()->get('oro_product.formatter.product_unit_label');

        /** @var ChoiceView $choiceView */
        foreach ($formView->vars['choices'] as $choiceView) {
            $key = array_search($choiceView->data, $productUnits, true);
            self::assertIsInt($key);

            self::assertEquals($productUnits[$key]->getCode(), $choiceView->value);
            self::assertEquals(
                $unitLabelFormatter->format($productUnits[$key]->getCode(), $compact),
                $choiceView->label
            );
        }
    }

    public function testCreateWhenHasProductAndSellFlagShouldHaveOnlyEnabledRelatedProductUnits(): void
    {
        /** @var ProductUnit $unitItemPrimary */
        $unitItemPrimary = $this->getReference('item');
        /** @var ProductUnit $unitEach */
        $unitEach = $this->getReference('each');
        /** @var ProductUnit $unitKg */
        $unitKg = $this->getReference('kg');
        /** @var ProductUnit $unitSetDisabled */
        $unitSetDisabled = $this->getReference('set');

        $product = (new Product())
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($unitItemPrimary))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitEach))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitKg))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitSetDisabled)->setSell(false));

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            ProductUnitChoiceType::class,
            null,
            ['product' => $product, 'sell' => true]
        );

        $productUnits = [
            $unitItemPrimary,
            $unitEach,
            $unitKg,
        ];
        self::assertArrayIntersectEquals(
            [
                'class' => ProductUnit::class,
                'compact' => false,
                'product' => $product,
                'sell' => true,
                'choices' => $productUnits,
            ],
            $form->getConfig()->getOptions()
        );

        $formView = $form->createView();

        self::assertContains('oro_product_unit_select', $formView->vars['block_prefixes']);

        self::assertCount(count($productUnits), $formView->vars['choices']);

        /** @var UnitLabelFormatterInterface $unitLabelFormatter */
        $unitLabelFormatter = self::getContainer()->get('oro_product.formatter.product_unit_label');

        /** @var ChoiceView $choiceView */
        foreach ($formView->vars['choices'] as $choiceView) {
            $key = array_search($choiceView->data, $productUnits, true);
            self::assertIsInt($key);

            self::assertEquals($productUnits[$key]->getCode(), $choiceView->value);
            self::assertEquals(
                $unitLabelFormatter->format($productUnits[$key]->getCode()),
                $choiceView->label
            );
        }
    }

    public function testCreateWhenHasProductAndSellFlagShouldHaveOnlyDisabledRelatedProductUnits(): void
    {
        /** @var ProductUnit $unitItemPrimary */
        $unitItemPrimary = $this->getReference('item');
        /** @var ProductUnit $unitEach */
        $unitEach = $this->getReference('each');
        /** @var ProductUnit $unitKg */
        $unitKg = $this->getReference('kg');
        /** @var ProductUnit $unitSetDisabled */
        $unitSetDisabled = $this->getReference('set');

        $product = (new Product())
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($unitItemPrimary))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitEach))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitKg))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitSetDisabled)->setSell(false));

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            ProductUnitChoiceType::class,
            null,
            ['product' => $product, 'sell' => false]
        );

        $productUnits = [
            $unitSetDisabled,
        ];
        self::assertArrayIntersectEquals(
            [
                'class' => ProductUnit::class,
                'compact' => false,
                'product' => $product,
                'sell' => false,
                'choices' => $productUnits,
            ],
            $form->getConfig()->getOptions()
        );

        $formView = $form->createView();

        self::assertContains('oro_product_unit_select', $formView->vars['block_prefixes']);

        self::assertCount(count($productUnits), $formView->vars['choices']);

        /** @var UnitLabelFormatterInterface $unitLabelFormatter */
        $unitLabelFormatter = self::getContainer()->get('oro_product.formatter.product_unit_label');

        /** @var ChoiceView $choiceView */
        foreach ($formView->vars['choices'] as $choiceView) {
            $key = array_search($choiceView->data, $productUnits, true);
            self::assertIsInt($key);

            self::assertEquals($productUnits[$key]->getCode(), $choiceView->value);
            self::assertEquals(
                $unitLabelFormatter->format($productUnits[$key]->getCode()),
                $choiceView->label
            );
        }
    }

    public function testSubmitWhenNoProduct(): void
    {
        /** @var ProductUnit $unitEach */
        $unitEach = $this->getReference('each');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            ProductUnitChoiceType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit($unitEach->getCode());

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertSame($unitEach, $form->getData());
    }

    public function testSubmitWhenNoProductAndInvalidChoice(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            ProductUnitChoiceType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit('invalid');

        self::assertFalse($form->isValid());
        self::assertFalse($form->isSynchronized());
        self::assertStringContainsString('The selected choice is invalid.', (string)$form->getErrors(true));
        self::assertNull($form->getData());
    }

    public function testSubmitWhenHasProduct(): void
    {
        /** @var ProductUnit $unitItemPrimary */
        $unitItemPrimary = $this->getReference('item');
        /** @var ProductUnit $unitEach */
        $unitEach = $this->getReference('each');

        $product = (new Product())
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($unitItemPrimary))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitEach));

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            ProductUnitChoiceType::class,
            null,
            ['product' => $product, 'csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit($unitItemPrimary->getCode());

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertSame($unitItemPrimary, $form->getData());
    }

    public function testSubmitWhenHasProductAndInvalidChoice(): void
    {
        /** @var ProductUnit $unitItemPrimary */
        $unitItemPrimary = $this->getReference('item');
        /** @var ProductUnit $unitEach */
        $unitEach = $this->getReference('each');

        $product = (new Product())
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($unitItemPrimary))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitEach));

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            ProductUnitChoiceType::class,
            null,
            ['product' => $product, 'csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit('kg');

        self::assertFalse($form->isValid());
        self::assertFalse($form->isSynchronized());
        self::assertStringContainsString('The selected choice is invalid.', (string)$form->getErrors(true));
        self::assertNull($form->getData());
    }

    public function testSubmitWhenHasProductAndDisabledUnit(): void
    {
        /** @var ProductUnit $unitItemPrimary */
        $unitItemPrimary = $this->getReference('item');
        /** @var ProductUnit $unitEach */
        $unitEach = $this->getReference('each');

        $product = (new Product())
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($unitItemPrimary))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit($unitEach)->setSell(false));

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            ProductUnitChoiceType::class,
            null,
            ['product' => $product, 'sell' => true, 'csrf_protection' => false, 'validation_groups' => false]
        );

        $form->submit($unitEach->getCode());

        self::assertFalse($form->isValid());
        self::assertFalse($form->isSynchronized());
        self::assertStringContainsString('The selected choice is invalid.', (string)$form->getErrors(true));
        self::assertNull($form->getData());
    }
}

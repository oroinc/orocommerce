<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\AbstractOrderLineItemType;
use Oro\Bundle\PricingBundle\Form\Type\PriceTypeSelectorType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractOrderLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait, EntityTrait;

    /**
     * @var AbstractOrderLineItemType
     */
    protected $formType;

    /** @var SectionProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $sectionProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sectionProvider = $this->createMock('Oro\Bundle\OrderBundle\Form\Section\SectionProvider');
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $unitSelectType = new EntityType(
            [
                'kg' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'kg']),
                'item' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item']),
            ],
            ProductUnitSelectionType::NAME
        );

        $priceType = PriceTypeGenerator::createPriceType($this);

        $orderPriceType = new PriceTypeSelectorType();

        return [
            new PreloadedExtension(
                [
                    ProductUnitSelectionType::class => $unitSelectType,
                    PriceType::class => $priceType,
                    PriceTypeSelectorType::class => $orderPriceType,
                    QuantityType::class => $this->getQuantityType(),
                ],
                []
            ),
        ];
    }

    public function testSettingsProviderMissing()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('SectionProvider not injected');

        $formType = $this->getFormType();

        $view = new FormView();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $formType->finishView($view, $form, []);
    }

    public function testConfigureOptions()
    {
        $expectedOptions = $this->getExpectedOptions();
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve();
        foreach ($resolver->getDefinedOptions() as $option) {
            $this->assertArrayHasKey($option, $expectedOptions);
            $this->assertArrayHasKey($option, $resolvedOptions);
            $this->assertEquals($expectedOptions[$option], $resolvedOptions[$option]);
        }
    }

    /**
     * @return array
     */
    abstract public function getExpectedOptions();

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        array $submittedData,
        OrderLineItem $expectedData,
        OrderLineItem $data = null
    ) {
        if (!$data) {
            $data = new OrderLineItem();
        }
        $form = $this->factory->create(get_class($this->formType), $data, $options);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function assertDefaultBuildViewCalled()
    {
        $view = new FormView();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $possibleOptions = [
            [
                'options' => ['currency' => 'USD'],
                'expected' => [
                    'page_component' => null,
                    'page_component_options' => ['currency' => 'USD'],
                ],
            ],
            [
                'options' => [
                    'currency' => 'USD',
                    'page_component' => 'test',
                    'page_component_options' => ['v2'],
                ],
                'expected' => [
                    'page_component' => 'test',
                    'page_component_options' => ['v2', 'currency' => 'USD'],

                ],
            ],
        ];

        foreach ($possibleOptions as $optionsData) {
            $this->formType->buildView($view, $form, $optionsData['options']);
            $this->assertBuildView($view, $optionsData['expected']);
        }
    }

    public function testFinishView()
    {
        $this->sectionProvider->expects($this->once())->method('getSections')->with(get_class($this->formType))
            ->willReturn($this->getExpectedSections());

        $view = new FormView();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $this->formType->finishView($view, $form, []);

        $this->assertEquals($this->getExpectedSections(), $view->vars['sections']);
    }

    /**
     * @return ArrayCollection
     */
    abstract protected function getExpectedSections();

    public function assertBuildView(FormView $view, array $expectedVars)
    {
        foreach ($expectedVars as $key => $val) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($val, $view->vars[$key]);
        }
    }

    /**
     * @return array
     */
    abstract public function submitDataProvider();

    /**
     * @return FormTypeInterface
     */
    abstract public function getFormType();
}

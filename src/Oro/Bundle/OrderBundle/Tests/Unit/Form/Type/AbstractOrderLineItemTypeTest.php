<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\AbstractOrderLineItemType;
use Oro\Bundle\PricingBundle\Form\Type\PriceTypeSelectorType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;

abstract class AbstractOrderLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait, EntityTrait;

    /**
     * @var AbstractOrderLineItemType
     */
    protected $formType;

    /** @var SectionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $sectionProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->sectionProvider = $this->getMock('Oro\Bundle\OrderBundle\Form\Section\SectionProvider');
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

        $priceType = PriceTypeGenerator::createPriceType();

        $orderPriceType = new PriceTypeSelectorType();
        $dateType = new OroDateType();

        return [
            new PreloadedExtension(
                [
                    $unitSelectType->getName() => $unitSelectType,
                    $priceType->getName() => $priceType,
                    $orderPriceType->getName() => $orderPriceType,
                    $dateType->getName() => $dateType,
                    QuantityTypeTrait::$name => $this->getQuantityType(),
                ],
                []
            ),
        ];
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage SectionProvider not injected
     */
    public function testSettingsProviderMissing()
    {
        $formType = $this->getFormType();

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
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
     *
     * @param array $options
     * @param array $submittedData
     * @param OrderLineItem $expectedData
     * @param OrderLineItem|null $data
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
        $form = $this->factory->create($this->formType, $data, $options);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function assertDefaultBuildViewCalled()
    {
        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

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
        $this->sectionProvider->expects($this->once())->method('getSections')->with($this->formType->getName())
            ->willReturn($this->getExpectedSections());

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->formType->finishView($view, $form, []);

        $this->assertEquals($this->getExpectedSections(), $view->vars['sections']);
    }

    /**
     * @return ArrayCollection
     */
    abstract protected function getExpectedSections();

    /**
     * @param FormView $view
     * @param array $expectedVars
     */
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

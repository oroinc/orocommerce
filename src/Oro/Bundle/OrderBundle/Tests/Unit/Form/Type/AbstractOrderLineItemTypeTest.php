<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\AbstractOrderLineItemType;
use Oro\Bundle\PricingBundle\Form\Type\PriceTypeSelectorType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractOrderLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait, EntityTrait;

    /** @var AbstractOrderLineItemType */
    protected $formType;

    /** @var SectionProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $sectionProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sectionProvider = $this->createMock(SectionProvider::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        return [
            new PreloadedExtension(
                [
                    ProductUnitSelectionType::class => new EntityTypeStub([
                        'kg' => $this->getEntity(ProductUnit::class, ['code' => 'kg']),
                        'item' => $this->getEntity(ProductUnit::class, ['code' => 'item']),
                    ]),
                    $priceType,
                    PriceTypeSelectorType::class => new PriceTypeSelectorType(),
                    $this->getQuantityType(),
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
        $form = $this->createMock(FormInterface::class);
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

    abstract public function getExpectedOptions(): array;

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
        $form = $this->createMock(FormInterface::class);

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
        $this->sectionProvider->expects($this->once())
            ->method('getSections')
            ->with(get_class($this->formType))
            ->willReturn($this->getExpectedSections());

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $this->formType->finishView($view, $form, []);

        $this->assertEquals($this->getExpectedSections(), $view->vars['sections']);
    }

    abstract protected function getExpectedSections(): ArrayCollection;

    public function assertBuildView(FormView $view, array $expectedVars)
    {
        foreach ($expectedVars as $key => $val) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($val, $view->vars[$key]);
        }
    }

    abstract public function submitDataProvider(): array;

    abstract public function getFormType(): FormTypeInterface;
}

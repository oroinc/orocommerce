<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\AbstractOrderLineItemType;
use OroB2B\Bundle\OrderBundle\Form\Type\PriceTypeSelectorType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;

abstract class AbstractOrderLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    /**
     * @var AbstractOrderLineItemType
     */
    protected $formType;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $unitSelectType = new EntityType(
            [
                'kg' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'kg', 'code'),
                'item' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', 'item', 'code'),
            ],
            ProductUnitSelectionType::NAME
        );

        $priceType = new PriceType();
        $priceType->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');

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

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->will($this->returnSelf());
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['currency'])
            ->will($this->returnSelf());

        $this->formType->configureOptions($resolver);
    }

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
                'expected' => ['page_component' => null, 'page_component_options' => ['currency' => 'USD']]
            ],
            [
                'options' => ['currency' => 'USD', 'page_component' => 'test', 'page_component_options' => ['v2']],
                'expected' => ['page_component' => 'test', 'page_component_options' => ['v2', 'currency' => 'USD']]
            ]
        ];

        foreach ($possibleOptions as $optionsData) {
            $this->formType->buildView($view, $form, $optionsData['options']);
            $this->assertBuildView($view, $optionsData['expected']);
        }
    }

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
     * @param string $className
     * @param int $id
     * @param string $property
     * @return object
     */
    protected function getEntity($className, $id, $property = 'id')
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty($property);
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}

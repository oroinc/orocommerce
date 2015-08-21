<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\AbstractOrderLineItemType;
use OroB2B\Bundle\OrderBundle\Form\Type\PriceTypeSelectorType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

abstract class AbstractOrderLineItemTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AbstractOrderLineItemType
     */
    protected $formType;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $productSelectType = new EntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1, 'id'),
                2 => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2, 'id'),
            ],
            ProductSelectType::NAME
        );

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
                    $productSelectType->getName() => $productSelectType,
                    $unitSelectType->getName() => $unitSelectType,
                    $priceType->getName() => $priceType,
                    $orderPriceType->getName() => $orderPriceType,
                    $dateType->getName() => $dateType,
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
     */
    public function testSubmit(array $options, array $submittedData, OrderLineItem $expectedData)
    {
        $form = $this->factory->create($this->formType, new OrderLineItem(), $options);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
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

<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;

class CustomerGroupTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CustomerGroupType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CustomerGroupType();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
            [
                $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 1),
                $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2),
            ]
        );

        $entityIdentifierType = new EntityIdentifierType([]);
        $priceListSelectStub = new PriceListSelectTypeStub();

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    $priceListSelectStub->getName() => $priceListSelectStub,
                    $entityIdentifierType->getName() => $entityIdentifierType
                ],
                []
            )
        ];
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        array $defaultData,
        array $viewData,
        array $submittedData,
        array $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertTrue($form->has('appendCustomers'));
        $this->assertTrue($form->has('removeCustomers'));

        $formConfig = $form->getConfig();
        $this->assertNull($formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'default' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'customer_group_name',
                    'priceList' => null
                ],
                'expectedData' => [
                    'name' => 'customer_group_name',
                    'priceList' => null
                ]
            ],
            'with list' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'customer_group_name',
                    'priceList' => 1
                ],
                'expectedData' => [
                    'name' => 'customer_group_name',
                    'priceList' => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2)
                ]
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_customer_group_type', $this->formType->getName());
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}

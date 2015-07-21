<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

class PriceListTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\PricingBundle\Entity\PriceList';
    const CUSTOMER_CLASS = 'OroB2B\Bundle\CustomerBundle\Entity\Customer';
    const CUSTOMER_GROUP_CLASS = 'OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup';
    const WEBSITE_CLASS = 'OroB2B\Bundle\WebsiteBundle\Entity\Website';

    /**
     * @var PriceListType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new PriceListType();
        $this->type->setDataClass(self::DATA_CLASS);
        $this->type->setCustomerClass(self::CUSTOMER_CLASS);
        $this->type->setCustomerGroupClass(self::CUSTOMER_GROUP_CLASS);
        $this->type->setWebsiteClass(self::WEBSITE_CLASS);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $currencySelectType = new CurrencySelectionTypeStub();
        $entityIdentifierType = new EntityIdentifierType(
            [
                1 => $this->getEntity(self::CUSTOMER_CLASS, 1),
                2 => $this->getEntity(self::CUSTOMER_CLASS, 2),
                3 => $this->getEntity(self::CUSTOMER_GROUP_CLASS, 3),
                4 => $this->getEntity(self::CUSTOMER_GROUP_CLASS, 4),
                5 => $this->getEntity(self::WEBSITE_CLASS, 5),
                6 => $this->getEntity(self::WEBSITE_CLASS, 6)
            ]
        );

        return [
            new PreloadedExtension(
                [
                    $currencySelectType->getName() => $currencySelectType,
                    $entityIdentifierType->getName() => $entityIdentifierType
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('currencies'));
        $this->assertTrue($form->has('appendCustomers'));
        $this->assertTrue($form->has('removeCustomers'));
        $this->assertTrue($form->has('appendCustomerGroups'));
        $this->assertTrue($form->has('removeCustomerGroups'));
        $this->assertTrue($form->has('appendWebsites'));
        $this->assertTrue($form->has('removeWebsites'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        if ($defaultData) {
            $existingPriceList = new PriceList();
            $class = new \ReflectionClass($existingPriceList);
            $prop  = $class->getProperty('id');
            $prop->setAccessible(true);

            $prop->setValue($existingPriceList, 42);
            $existingPriceList->setName($defaultData['name']);

            foreach ($defaultData['currencies'] as $currency) {
                $existingPriceList->addCurrencyByCode($currency);
            }

            $defaultData = $existingPriceList;
        }

        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        if (isset($existingPriceList)) {
            $this->assertEquals($existingPriceList, $form->getViewData());
        } else {
            $this->assertNull($form->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var PriceList $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['name'], $result->getName());
        $this->assertEquals($expectedData['currencies'], array_values($result->getCurrencies()));
        $this->assertEquals($expectedData['appendCustomers'], $form->get('appendCustomers')->getData());
        $this->assertEquals($expectedData['removeCustomers'], $form->get('removeCustomers')->getData());
        $this->assertEquals($expectedData['appendCustomerGroups'], $form->get('appendCustomerGroups')->getData());
        $this->assertEquals($expectedData['removeCustomerGroups'], $form->get('removeCustomerGroups')->getData());
        $this->assertEquals($expectedData['appendWebsites'], $form->get('appendWebsites')->getData());
        $this->assertEquals($expectedData['removeWebsites'], $form->get('removeWebsites')->getData());
    }
    
    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new price list' => [
                'defaultData' => null,
                'submittedData' => [
                    'name' => 'Test Price List',
                    'currencies' => [],
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ],
                'expectedData' => [
                    'name' => 'Test Price List',
                    'currencies' => [],
                    'default' => false,
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ]
            ],
            'update price list' => [
                'defaultData' => [
                    'name' => 'Test Price List',
                    'currencies' => ['USD', 'UAH'],
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ],
                'submittedData' => [
                    'name' => 'Test Price List 01',
                    'currencies' => ['EUR', 'USD'],
                    'appendCustomers' => [1],
                    'removeCustomers' => [2],
                    'appendCustomerGroups' => [3],
                    'removeCustomerGroups' => [4],
                    'appendWebsites' => [5],
                    'removeWebsites' => [6],
                ],
                'expectedData' => [
                    'name' => 'Test Price List 01',
                    'currencies' => ['EUR', 'USD'],
                    'appendCustomers' => [$this->getEntity(self::CUSTOMER_CLASS, 1)],
                    'removeCustomers' => [$this->getEntity(self::CUSTOMER_CLASS, 2)],
                    'appendCustomerGroups' => [
                        $this->getEntity(self::CUSTOMER_GROUP_CLASS, 3)
                    ],
                    'removeCustomerGroups' => [
                        $this->getEntity(self::CUSTOMER_GROUP_CLASS, 4)
                    ],
                    'appendWebsites' => [$this->getEntity(self::WEBSITE_CLASS, 5)],
                    'removeWebsites' => [$this->getEntity(self::WEBSITE_CLASS, 6)],
                ]
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(PriceListType::NAME, $this->type->getName());
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

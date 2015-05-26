<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

class PriceListTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PriceListType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new PriceListType();
    }

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

        return [
            new PreloadedExtension([$currencySelectType->getName() => $currencySelectType], [])
        ];
    }

    /**
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
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
        $this->assertEquals($expectedData['currencies'], $result->getCurrencies());
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
                    'currencies' => []
                ],
                'expectedData' => [
                    'name' => 'Test Price List',
                    'currencies' => [],
                    'default' => false
                ]
            ],
            'update price list' => [
                'defaultData' => [
                    'name' => 'Test Price List',
                    'currencies' => ['USD', 'UAH']
                ],
                'submittedData' => [
                    'name' => 'Test Price List 01',
                    'currencies' => ['USD', 'EUR']
                ],
                'expectedData' => [
                    'name' => 'Test Price List 01',
                    'currencies' => ['USD', 'EUR']
                ]
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(PriceListType::NAME, $this->type->getName());
    }
}

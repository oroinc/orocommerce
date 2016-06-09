<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceAttributePriceListType;

class PriceAttributePriceListTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList';

    /**
     * @var PriceAttributePriceListType
     */
    protected $priceAttributePriceListType;

    protected function setUp()
    {
        parent::setUp();
        $this->priceAttributePriceListType = new PriceAttributePriceListType();
        $this->priceAttributePriceListType->setDataClass(self::DATA_CLASS);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $currencySelectType = new CurrencySelectionTypeStub();

        return [
            new PreloadedExtension(
                [
                    $currencySelectType->getName() => $currencySelectType,
                ],
                []
            ),
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->priceAttributePriceListType);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('currencies'));
    }

    public function testGetName()
    {
        $this->assertEquals(PriceAttributePriceListType::NAME, $this->priceAttributePriceListType->getName());
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
            $existingPriceAttributePriceList = new PriceAttributePriceList();
            $class = new \ReflectionClass($existingPriceAttributePriceList);
            $prop = $class->getProperty('id');
            $prop->setAccessible(true);

            $prop->setValue($existingPriceAttributePriceList, 42);
            $existingPriceAttributePriceList->setName($defaultData['name']);

            foreach ($defaultData['currencies'] as $currency) {
                $existingPriceAttributePriceList->addCurrencyByCode($currency);
            }

            $defaultData = $existingPriceAttributePriceList;
        }

        $form = $this->factory->create($this->priceAttributePriceListType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        if (isset($existingPriceAttributePriceList)) {
            $this->assertEquals($existingPriceAttributePriceList, $form->getViewData());
        } else {
            $this->assertNull($form->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var PriceAttributePriceList $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['name'], $result->getName());
        $this->assertEquals($expectedData['currencies'], array_values($result->getCurrencies()));
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
                    'name' => 'Test Price Attribute',
                    'currencies' => [],
                ],
                'expectedData' => [
                    'name' => 'Test Price Attribute',
                    'currencies' => [],
                ],
            ],
            'update price list' => [
                'defaultData' => [
                    'name' => 'Test Price Attribute',
                    'currencies' => ['USD', 'UAH'],
                ],
                'submittedData' => [
                    'name' => 'Test Price Attribute 01',
                    'currencies' => ['EUR', 'USD'],
                ],
                'expectedData' => [
                    'name' => 'Test Price Attribute 01',
                    'currencies' => ['EUR', 'USD'],
                ],
            ],
        ];
    }
}

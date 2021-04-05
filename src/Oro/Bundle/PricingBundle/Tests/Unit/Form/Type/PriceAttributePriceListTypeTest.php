<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceAttributePriceListType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class PriceAttributePriceListTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList';

    /**
     * @var PriceAttributePriceListType
     */
    protected $priceAttributePriceListType;

    protected function setUp(): void
    {
        $this->priceAttributePriceListType = new PriceAttributePriceListType();
        $this->priceAttributePriceListType->setDataClass(self::DATA_CLASS);
        parent::setUp();
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
                    $this->priceAttributePriceListType,
                    CurrencySelectionType::class => $currencySelectType,
                ],
                []
            ),
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(PriceAttributePriceListType::class);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('currencies'));
    }

    public function testSubmitWithoutDefaultData()
    {
        $submittedData = [
            'name' => 'Test Price Attribute',
            'currencies' => [],
            'enabledInExport' => 0
        ];
        $expectedData = $submittedData;

        $form = $this->factory->create(PriceAttributePriceListType::class, null, []);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var PriceAttributePriceList $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['name'], $result->getName());
        $this->assertEquals($expectedData['currencies'], array_values($result->getCurrencies()));
        $this->assertEquals($expectedData['enabledInExport'], $result->isEnabledInExport());
    }

    public function testSubmitWithDefaultData()
    {
        $submittedData = [
            'name' => 'Test Price Attribute 01',
            'fieldName' => 'pa01',
            'currencies' => ['EUR', 'USD'],
            'enabledInExport' => 1
        ];

        $expectedData = $submittedData;
        $defaultData = [
            'name' => 'Test Price Attribute',
            'fieldName' => 'pa02',
            'currencies' => ['USD', 'UAH'],
            'enabledInExport' => 0
        ];
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
        $form = $this->factory->create(PriceAttributePriceListType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($existingPriceAttributePriceList, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var PriceAttributePriceList $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['name'], $result->getName());
        $this->assertEquals($expectedData['currencies'], array_values($result->getCurrencies()));
        $this->assertEquals($expectedData['enabledInExport'], $result->isEnabledInExport());
    }
}

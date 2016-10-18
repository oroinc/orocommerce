<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class PriceRuleTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|CurrencyConfigManager $configManager */
        $configManager = $this->getMockBuilder(CurrencyConfigManager::class)->disableOriginalConstructor()->getMock();
        $configManager->method('getCurrencyList')->willReturn(['USD', 'EUR']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings $localeSettings */
        $localeSettings = $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock();

        return [
            new PreloadedExtension(
                [
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $configManager,
                        $localeSettings,
                        $this->getMockBuilder(CurrencyNameHelper::class)->disableOriginalConstructor()->getMock()
                    ),
                    'entity' => new EntityType(['item' => (new ProductUnit())->setCode('item')])
                ],
                []
            )
        ];
    }

    public function testSubmit()
    {
        $form = $this->factory->create(new PriceRuleType(), new PriceRule());

        $form->submit([
            PriceRuleType::CURRENCY => 'USD',
            PriceRuleType::PRIORITY => 1,
            PriceRuleType::QUANTITY => 10,
            PriceRuleType::RULE => 'rule as a string',
            PriceRuleType::RULE_CONDITION => 'condition as a string',
            PriceRuleType::PRODUCT_UNIT => 'item',
            PriceRuleType::PRODUCT_UNIT_EXPRESSION => 'product.unit',
            PriceRuleType::CURRENCY_EXPRESSION => 'product.msrp.currency',
            PriceRuleType::QUANTITY_EXPRESSION => 'product.quantity',
        ]);

        $expected = new PriceRule();
        $expected->setCurrency('USD')
            ->setPriority(1)
            ->setQuantity(10)
            ->setRule('rule as a string')
            ->setCurrencyExpression('product.msrp.currency')
            ->setQuantityExpression('product.quantity')
            ->setProductUnitExpression('product.unit')
            ->setRuleCondition('condition as a string')
            ->setProductUnit((new ProductUnit())->setCode('item'));

        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $form->getData());
    }
}

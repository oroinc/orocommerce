<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceRuleType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class PriceRuleTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $configManager->method('get')->willReturn(['USD', 'EUR']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings $localeSettings */
        $localeSettings = $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock();

        return [
            new PreloadedExtension(
                [
                    CurrencySelectionType::NAME => new CurrencySelectionType($configManager, $localeSettings),
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
            PriceRuleType::PRODUCT_UNIT => 'item'
        ]);

        $expected = new PriceRule();
        $expected->setCurrency('USD')
            ->setPriority(1)
            ->setQuantity(10)
            ->setRule('rule as a string')
            ->setRuleCondition('condition as a string')
            ->setProductUnit((new ProductUnit())->setCode('item'));

        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $form->getData());
    }
}

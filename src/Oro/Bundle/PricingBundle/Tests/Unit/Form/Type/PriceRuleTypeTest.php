<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class PriceRuleTypeTest extends FormIntegrationTestCase
{
    use PriceRuleEditorAwareTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|CurrencyProviderInterface $currencyProvider */
        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $currencyProvider->method('getCurrencyList')->willReturn(['USD', 'EUR']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings $localeSettings */
        $localeSettings = $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock();

        return [
            new PreloadedExtension(
                array_merge(
                    [
                        CurrencySelectionType::NAME => new CurrencySelectionType(
                            $currencyProvider,
                            $localeSettings,
                            $this->getMockBuilder(CurrencyNameHelper::class)->disableOriginalConstructor()->getMock()
                        ),
                        'entity' => new EntityType(['item' => (new ProductUnit())->setCode('item')])
                    ],
                    $this->getPriceRuleEditorExtension()
                ),
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

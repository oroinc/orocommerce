<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class PriceRuleTypeTest extends FormIntegrationTestCase
{
    use PriceRuleEditorAwareTestTrait;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        return [
            new PreloadedExtension(
                array_merge([
                    new CurrencySelectionType(
                        $currencyProvider,
                        $this->createMock(LocaleSettings::class),
                        $this->createMock(CurrencyNameHelper::class)
                    ),
                    EntityType::class => new EntityTypeStub(['item' => (new ProductUnit())->setCode('item')])
                ], $this->getPriceRuleEditorExtension()),
                []
            )
        ];
    }

    public function testSubmit()
    {
        $form = $this->factory->create(PriceRuleType::class, new PriceRule());

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
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }
}

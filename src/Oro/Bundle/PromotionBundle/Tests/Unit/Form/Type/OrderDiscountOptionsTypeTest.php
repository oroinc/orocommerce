<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\MultiCurrencyType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Oro\Bundle\PromotionBundle\Form\Type\OrderDiscountOptionsType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class OrderDiscountOptionsTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OrderDiscountOptionsType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new OrderDiscountOptionsType();
    }

    public function testGetName()
    {
        $this->assertEquals(OrderDiscountOptionsType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(OrderDiscountOptionsType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(DiscountOptionsType::NAME, $this->formType->getParent());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        /** @var Translator|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    MultiCurrencyType::NAME => new MultiCurrencyType(),
                    CurrencySelectionType::NAME => new CurrencySelectionTypeStub(),
                    DiscountOptionsType::NAME => new DiscountOptionsType()
                ],
                [
                    'form' => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}

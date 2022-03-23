<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\MultiCurrencyType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Oro\Bundle\PromotionBundle\Form\Type\OrderDiscountOptionsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class OrderDiscountOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var OrderDiscountOptionsType */
    private $formType;

    protected function setUp(): void
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
        $this->assertEquals(DiscountOptionsType::class, $this->formType->getParent());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    new MultiCurrencyType(),
                    new DiscountOptionsType(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub()
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}

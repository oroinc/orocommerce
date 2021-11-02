<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\PricingBundle\Form\Extension\FrontendPriceFormExtension;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class FrontendPriceFormExtensionTest extends FormIntegrationTestCase
{
    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $userCurrencyManager->method('getAvailableCurrencies')
            ->willReturn(['USD', 'EUR']);
        $userCurrencyManager->method('getUserCurrency')
            ->willReturn('EUR');

        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        $currencySelectionType = new CurrencySelectionTypeStub();

        return [
            new PreloadedExtension(
                [
                    PriceType::class => $priceType,
                    CurrencySelectionType::class => $currencySelectionType,
                ],
                [
                    PriceType::class => [
                        new FrontendPriceFormExtension($this->frontendHelper, $userCurrencyManager),
                    ],

                ]
            ),
        ];
    }

    public function testSubmitNotFrontend()
    {
        $this->frontendHelper->method('isFrontendRequest')
            ->willReturn(false);

        $form = $this->factory->create(PriceType::class);
        $form->setData(null);
        $this->assertNull($form->getData());
        $this->assertNull($form->get('currency')->getData());
        $this->assertNull($form->getConfig()->getOption('additional_currencies'));
        $this->assertNull($form->getConfig()->getOption('currencies_list'));
        $this->assertNull($form->getConfig()->getOption('default_currency'));
    }

    public function testSubmit()
    {
        $this->frontendHelper->method('isFrontendRequest')
            ->willReturn(true);

        $form = $this->factory->create(PriceType::class);
        $form->setData(null);
        $formData = $form->getData();
        $this->assertNull($formData);
        $this->assertEquals('EUR', $form->get('currency')->getData());
        $this->assertNull($form->getConfig()->getOption('additional_currencies'));
        $this->assertEquals(['USD', 'EUR'], $form->getConfig()->getOption('currencies_list'));
        $this->assertEquals('EUR', $form->getConfig()->getOption('default_currency'));
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\PricingBundle\Form\Extension\FrontendPriceFormExtension;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class FrontendPriceFormExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $frontendHelper;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userCurrencyManager;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->userCurrencyManager->method('getAvailableCurrencies')
            ->willReturn(['USD', 'EUR']);

        $this->userCurrencyManager->method('getUserCurrency')
            ->willReturn('EUR');

        $currencySelectionType = new CurrencySelectionTypeStub();

        return [
            new PreloadedExtension(
                [
                    PriceType::NAME => PriceTypeGenerator::createPriceType($this),
                    $currencySelectionType->getName() => $currencySelectionType,
                ],
                [
                    PriceType::NAME => [
                        new FrontendPriceFormExtension($this->frontendHelper, $this->userCurrencyManager),
                    ],

                ]
            ),
        ];
    }

    public function testSubmitNotFrontend()
    {
        $this->frontendHelper->method('isFrontendRequest')
            ->willReturn(false);

        $form = $this->factory->create(PriceType::NAME);
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

        $form = $this->factory->create(PriceType::NAME);
        $form->setData(null);
        $formData = $form->getData();
        $this->assertNull($formData);
        $this->assertEquals($form->get('currency')->getData(), 'EUR');
        $this->assertNull($form->getConfig()->getOption('additional_currencies'));
        $this->assertEquals($form->getConfig()->getOption('currencies_list'), ['USD', 'EUR']);
        $this->assertEquals($form->getConfig()->getOption('default_currency'), 'EUR');
    }
}

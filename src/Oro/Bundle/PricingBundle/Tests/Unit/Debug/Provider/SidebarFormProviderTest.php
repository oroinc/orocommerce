<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Debug\Provider\CombinedPriceListActivationRulesProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\SidebarFormProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SidebarFormProviderTest extends TestCase
{
    use EntityTrait;

    private DebugProductPricesPriceListRequestHandler|MockObject $requestHandler;
    private FormFactoryInterface|MockObject $formFactory;
    private CombinedPriceListActivationRulesProvider|MockObject $activationRulesProvider;
    private SidebarFormProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestHandler = $this->createMock(DebugProductPricesPriceListRequestHandler::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->activationRulesProvider = $this->createMock(CombinedPriceListActivationRulesProvider::class);

        $this->provider = new SidebarFormProvider(
            $this->requestHandler,
            $this->activationRulesProvider,
            $this->formFactory
        );
    }

    public function testGetIndexPageSidebarFormElements()
    {
        $activePriceList = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $priceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $currencies = ['EUR', 'UAH', 'USD'];
        $selectedCurrencies = ['EUR', 'USD'];
        $priceList->setCurrencies($currencies);
        $customer = $this->getEntity(Customer::class, ['id' => 2]);
        $showTierPrices = true;

        $this->requestHandler->expects($this->any())
            ->method('getPriceList')
            ->willReturn($priceList);
        $this->requestHandler->expects($this->any())
            ->method('getCurrentActivePriceList')
            ->willReturn($activePriceList);
        $this->requestHandler->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customer);
        $this->requestHandler->expects($this->any())
            ->method('getShowTierPrices')
            ->willReturn($showTierPrices);
        $this->requestHandler->expects($this->any())
            ->method('getPriceListSelectedCurrencies')
            ->willReturn($selectedCurrencies);

        $currenciesFormView = $this->createMock(FormView::class);
        $showTierPricesFormView = $this->createMock(FormView::class);
        $customersFormView = $this->createMock(FormView::class);

        $this->assertIndexPageSidebarFormCreation(
            $currenciesFormView,
            $showTierPricesFormView,
            $customersFormView,
            $currencies,
            $selectedCurrencies,
            $showTierPrices,
            $customer
        );

        $expected = [
            'currencies' => $currenciesFormView,
            'showTierPrices' => $showTierPricesFormView,
            'customers' => $customersFormView
        ];

        $this->assertEquals($expected, $this->provider->getIndexPageSidebarFormElements());
    }

    public function testGetViewPageSidebarFormElements()
    {
        $product = $this->getEntity(Product::class, ['id' => 5]);
        $fullChainCpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $selectedDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $customer = $this->getEntity(Customer::class, ['id' => 2]);
        $showDetailedAssignmentInfo = true;
        $showDevelopersInfo = true;

        $this->requestHandler->expects($this->any())
            ->method('getFullChainCpl')
            ->willReturn($fullChainCpl);
        $this->requestHandler->expects($this->any())
            ->method('getSelectedDate')
            ->willReturn($selectedDate);
        $this->requestHandler->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customer);
        $this->requestHandler->expects($this->any())
            ->method('getShowDetailedAssignmentInfo')
            ->willReturn($showDetailedAssignmentInfo);
        $this->requestHandler->expects($this->any())
            ->method('getShowDevelopersInfo')
            ->willReturn($showDevelopersInfo);

        $dateFormView = $this->createMock(FormView::class);
        $customersFormView = $this->createMock(FormView::class);
        $showDetailedAssignmentInfoFormView = $this->createMock(FormView::class);
        $showDevelopersInfoFormView = $this->createMock(FormView::class);

        $this->assertViewPageSidebarFormCreation(
            $dateFormView,
            $showDetailedAssignmentInfoFormView,
            $showDevelopersInfoFormView,
            $customersFormView,
            $fullChainCpl,
            $selectedDate,
            $showDetailedAssignmentInfo,
            $showDevelopersInfo,
            $customer
        );

        $expected = [
            'product' => $product,
            'customers' => $customersFormView,
            'date' => $dateFormView,
            'showDetailedAssignmentInfo' => $showDetailedAssignmentInfoFormView,
            'showDevelopersInfo' => $showDevelopersInfoFormView
        ];

        $this->assertEquals($expected, $this->provider->getViewPageSidebarFormElements($product));
    }

    private function assertIndexPageSidebarFormCreation(
        FormView $currenciesFormView,
        FormView $showTierPricesFormView,
        FormView $customersFormView,
        array $currencies,
        array $selectedCurrencies,
        bool $showTierPrices,
        Customer $customer
    ): void {
        $currenciesForm = $this->createMock(FormInterface::class);
        $currenciesForm->expects($this->once())
            ->method('createView')
            ->willReturn($currenciesFormView);
        $showTierPricesForm = $this->createMock(FormInterface::class);
        $showTierPricesForm->expects($this->once())
            ->method('createView')
            ->willReturn($showTierPricesFormView);
        $customersForm = $this->createMock(FormInterface::class);
        $customersForm->expects($this->once())
            ->method('createView')
            ->willReturn($customersFormView);

        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [
                    CurrencySelectionType::class,
                    null,
                    [
                        'label' => 'oro.pricing.productprice.debug.currencies.label',
                        'expanded' => true,
                        'compact' => true,
                        'multiple' => true,
                        'csrf_protection' => false,
                        'required' => false,
                        'currencies_list' => $currencies,
                        'data' => $selectedCurrencies,
                    ],
                    $currenciesForm
                ],
                [
                    CheckboxType::class,
                    null,
                    [
                        'label' => 'oro.pricing.productprice.debug.show_tier_prices.label',
                        'required' => false,
                        'data' => $showTierPrices,
                    ],
                    $showTierPricesForm
                ],
                [
                    CustomerSelectType::class,
                    $customer,
                    [
                        'label' => 'oro.customer.entity_label',
                        'required' => false,
                        'empty_data' => $customer,
                        'create_enabled' => false
                    ],
                    $customersForm
                ]
            ]);
    }

    private function assertViewPageSidebarFormCreation(
        FormView $dateFormView,
        FormView $showDetailedAssignmentInfoFormView,
        FormView $showDevelopersInfoFormView,
        FormView $customersFormView,
        CombinedPriceList $fullChainCpl,
        \DateTime $selectedDate,
        bool $showDetailedAssignmentInfo,
        bool $showDevelopersInfo,
        Customer $customer
    ): void {
        $dateForm = $this->createMock(FormInterface::class);
        $dateForm->expects($this->once())
            ->method('createView')
            ->willReturn($dateFormView);

        $showDetailedAssignmentInfoForm = $this->createMock(FormInterface::class);
        $showDetailedAssignmentInfoForm->expects($this->once())
            ->method('createView')
            ->willReturn($showDetailedAssignmentInfoFormView);

        $showDevelopersInfoForm = $this->createMock(FormInterface::class);
        $showDevelopersInfoForm->expects($this->once())
            ->method('createView')
            ->willReturn($showDevelopersInfoFormView);

        $customersForm = $this->createMock(FormInterface::class);
        $customersForm->expects($this->once())
            ->method('createView')
            ->willReturn($customersFormView);

        $this->activationRulesProvider->expects($this->once())
            ->method('hasActivationRules')
            ->with($fullChainCpl)
            ->willReturn(true);

        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [
                    OroDateTimeType::class,
                    $selectedDate,
                    [
                        'label' => 'oro.pricing.productprice.debug.show_for_date.label',
                        'required' => false,
                        'empty_data' => $selectedDate,
                        'disabled' => false
                    ],
                    $dateForm
                ],
                [
                    CheckboxType::class,
                    null,
                    [
                        'label' => 'oro.pricing.productprice.debug.show_detailed_assignment_info.label',
                        'required' => false,
                        'data' => $showDetailedAssignmentInfo
                    ],
                    $showDetailedAssignmentInfoForm
                ],
                [
                    CheckboxType::class,
                    null,
                    [
                        'label' => 'oro.pricing.productprice.debug.show_developers_info.label',
                        'required' => false,
                        'data' => $showDevelopersInfo
                    ],
                    $showDevelopersInfoForm
                ],
                [
                    CustomerSelectType::class,
                    $customer,
                    [
                        'label' => 'oro.customer.entity_label',
                        'required' => false,
                        'empty_data' => $customer,
                        'create_enabled' => false
                    ],
                    $customersForm
                ]
            ]);
    }
}

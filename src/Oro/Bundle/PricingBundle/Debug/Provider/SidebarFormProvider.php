<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Provides Form items for price calculation details sidebars.
 */
class SidebarFormProvider implements SidebarFormProviderInterface
{
    public function __construct(
        private DebugProductPricesPriceListRequestHandler $requestHandler,
        private CombinedPriceListActivationRulesProvider $activationRulesProvider,
        private FormFactoryInterface $formFactory
    ) {
    }

    public function getIndexPageSidebarFormElements(): array
    {
        $sidebarData = [];

        $currenciesForm = $this->createCurrenciesForm();
        if ($currenciesForm) {
            $sidebarData['currencies'] = $currenciesForm->createView();
        }
        $sidebarData['showTierPrices'] = $this->createShowTierPricesForm()->createView();
        $sidebarData['customers'] = $this->createCustomersForm()->createView();

        return $sidebarData;
    }

    public function getViewPageSidebarFormElements(?Product $product): array
    {
        $sidebarData = [
            'product' => $product
        ];

        $sidebarData['customers'] = $this->createCustomersForm()->createView();
        $sidebarData['date'] = $this->createDateForm()->createView();
        $sidebarData['showDetailedAssignmentInfo'] = $this->createShowDetailedAssignmentInfoForm()->createView();
        $sidebarData['showDevelopersInfo'] = $this->createShowDevelopersInfoForm()->createView();

        return $sidebarData;
    }

    private function createCurrenciesForm(): ?FormInterface
    {
        $availableCurrencies = $this->getPriceListCurrencies();
        $priceList = $this->requestHandler->getPriceList();
        $selectedCurrencies = [];
        if ($priceList) {
            $selectedCurrencies = $this->requestHandler->getPriceListSelectedCurrencies($priceList);
        }

        if (count($availableCurrencies) <= 1) {
            return null;
        }

        return $this->formFactory->create(
            CurrencySelectionType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.debug.currencies.label',
                'expanded' => true,
                'compact' => true,
                'multiple' => true,
                'csrf_protection' => false,
                'required' => false,
                'currencies_list' => $availableCurrencies,
                'data' => $selectedCurrencies,
            ]
        );
    }

    private function createShowTierPricesForm(): FormInterface
    {
        return $this->formFactory->create(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.debug.show_tier_prices.label',
                'required' => false,
                'data' => $this->requestHandler->getShowTierPrices(),
            ]
        );
    }

    private function createShowDetailedAssignmentInfoForm(): FormInterface
    {
        return $this->formFactory->create(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.debug.show_detailed_assignment_info.label',
                'required' => false,
                'data' => $this->requestHandler->getShowDetailedAssignmentInfo(),
            ]
        );
    }

    private function createShowDevelopersInfoForm(): FormInterface
    {
        return $this->formFactory->create(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.debug.show_developers_info.label',
                'required' => false,
                'data' => $this->requestHandler->getShowDevelopersInfo(),
            ]
        );
    }

    private function createCustomersForm(): FormInterface
    {
        $customer = $this->requestHandler->getCustomer();

        return $this->formFactory->create(
            CustomerSelectType::class,
            $customer,
            [
                'label' => 'oro.customer.entity_label',
                'required' => false,
                'empty_data' => $customer,
                'create_enabled' => false
            ]
        );
    }

    private function createDateForm(): FormInterface
    {
        $date = $this->requestHandler->getSelectedDate();
        $fullChainCpl = $this->requestHandler->getFullChainCpl();

        return $this->formFactory->create(
            OroDateTimeType::class,
            $date,
            [
                'label' => 'oro.pricing.productprice.debug.show_for_date.label',
                'required' => false,
                'empty_data' => $date,
                'disabled' => !$this->activationRulesProvider->hasActivationRules($fullChainCpl)
            ]
        );
    }

    private function getPriceListCurrencies(): array
    {
        $currencies = (array)$this->requestHandler->getPriceList()?->getCurrencies();
        sort($currencies);

        return $currencies;
    }
}

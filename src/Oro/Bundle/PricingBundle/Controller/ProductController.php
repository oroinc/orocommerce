<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for sidebar placeholder shown on product index page.
 */
class ProductController extends Controller
{
    /**
     * @Route("/sidebar", name="oro_pricing_price_product_sidebar")
     * @Template
     *
     * @param Request $request
     * @return array
     */
    public function sidebarAction(Request $request)
    {
        $sidebarData = [];

        $currenciesForm = $this->createCurrenciesForm($request);
        if ($currenciesForm) {
            $sidebarData['currencies'] = $currenciesForm->createView();
        }

        if ($this->isPriceListsEnabled()) {
            $sidebarData['priceList'] = $this->createPriceListForm()->createView();
            $sidebarData['showTierPrices'] = $this->createShowTierPricesForm()->createView();
        }

        return $sidebarData;
    }

    /**
     * @return Form
     */
    protected function createPriceListForm()
    {
        $priceList = $this->getPriceListHandler()->getPriceList();

        return $this->createForm(
            PriceListSelectType::class,
            $priceList,
            [
                'create_enabled' => false,
                'placeholder' => false,
                'empty_data' => $priceList,
                'configs' => ['allowClear' => false],
                'label' => 'oro.pricing.pricelist.entity_label',
            ]
        );
    }

    /**
     * @param Request $request
     * @return Form|null
     */
    protected function createCurrenciesForm(Request $request)
    {
        if ($this->isPriceListsEnabled()) {
            $priceList = $this->getPriceListHandler()->getPriceList();
            $availableCurrencies = $priceList->getCurrencies();
            $selectedCurrencies = $this->getPriceListHandler()->getPriceListSelectedCurrencies($priceList);
            $showForm = true;
        } else {
            $availableCurrencies = $this->container->get('oro_pricing.user_currency_manager')->getAvailableCurrencies();
            $selectedCurrencies = $request->get(PriceListRequestHandlerInterface::PRICE_LIST_CURRENCY_KEY);
            $showForm = count($availableCurrencies) > 1;
        }

        if (!$showForm) {
            return null;
        }

        return $this->createForm(
            CurrencySelectionType::class,
            null,
            [
                'label' => 'oro.pricing.pricelist.currencies.label',
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

    /**
     * @return Form
     */
    protected function createShowTierPricesForm()
    {
        return $this->createForm(
            CheckboxType::class,
            null,
            [
                'label' => 'oro.pricing.productprice.show_tier_prices.label',
                'required' => false,
                'data' => $this->getPriceListHandler()->getShowTierPrices(),
            ]
        );
    }

    /**
     * @return PriceListRequestHandlerInterface
     */
    protected function getPriceListHandler()
    {
        return $this->get('oro_pricing.model.price_list_request_handler');
    }

    /**
     * @return bool
     */
    protected function isPriceListsEnabled(): bool
    {
        return $this->container->get('oro_featuretoggle.checker.feature_checker')->isFeatureEnabled('oro_price_lists');
    }
}

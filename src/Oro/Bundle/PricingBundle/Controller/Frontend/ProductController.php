<?php

namespace Oro\Bundle\PricingBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Form\Form;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;

class ProductController extends Controller
{
    /**
     * @Route("/sidebar", name="oro_pricing_frontend_product_sidebar")
     * @Template
     *
     * @return array
     */
    public function sidebarAction()
    {
        return [
            'currencies' => $this->createCurrenciesForm()->createView(),
            'showTierPrices' => $this->createShowTierPricesForm()->createView(),
        ];
    }

    /**
     * @return Form
     */
    protected function createCurrenciesForm()
    {
        $relationsProvider = $this->get('oro_customer.provider.account_user_relations_provider');

        $priceList = $this->get('oro_pricing.model.price_list_tree_handler')
            ->getPriceList($relationsProvider->getAccountIncludingEmpty($this->getUser()));

        $currenciesList = null;
        $selectedCurrencies = [];
        if ($priceList) {
            if ($priceList->getCurrencies()) {
                $currenciesList = $priceList->getCurrencies();
            }
            $selectedCurrencies = $this->getHandler()->getPriceListSelectedCurrencies($priceList);
        }

        $formOptions = [
            'label' => 'oro.pricing.productprice.currency.label',
            'compact' => true,
            'required' => false,
            'empty_value' => false,
            'currencies_list' => $currenciesList,
            'data' => reset($selectedCurrencies),
        ];

        return $this->createForm(CurrencySelectionType::NAME, null, $formOptions);
    }

    /**
     * @return Form
     */
    protected function createShowTierPricesForm()
    {
        return $this->createForm(
            'checkbox',
            null,
            [
                'label' => 'oro.pricing.productprice.show_tier_prices.label',
                'required' => false,
                'data' => $this->getHandler()->getShowTierPrices(),
            ]
        );
    }

    /**
     * @return PriceListRequestHandler
     */
    protected function getHandler()
    {
        return $this->get('oro_pricing.model.price_list_request_handler');
    }
}

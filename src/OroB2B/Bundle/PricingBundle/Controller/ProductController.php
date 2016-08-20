<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Form\Form;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class ProductController extends Controller
{
    /**
     * @Route("/sidebar", name="orob2b_pricing_price_product_sidebar")
     * @Template
     *
     * @return array
     */
    public function sidebarAction()
    {
        return [
            'priceList' => $this->createPriceListForm()->createView(),
            'currencies' => $this->createCurrenciesForm()->createView(),
            'showTierPrices' => $this->createShowTierPricesForm()->createView(),
        ];
    }

    /**
     * @return Form
     */
    protected function createPriceListForm()
    {
        $priceList = $this->getPriceListHandler()->getPriceList();

        return $this->createForm(
            PriceListSelectType::NAME,
            $priceList,
            [
                'create_enabled' => false,
                'empty_value' => false,
                'empty_data' => $priceList,
                'configs' => ['allowClear' => false],
                'label' => 'oro.pricing.pricelist.entity_label',
            ]
        );
    }

    /**
     * @return Form
     */
    protected function createCurrenciesForm()
    {
        $priceList = $this->getPriceListHandler()->getPriceList();
        return $this->createForm(
            CurrencySelectionType::NAME,
            null,
            [
                'label' => false,
                'expanded' => true,
                'compact' => true,
                'multiple' => true,
                'csrf_protection' => false,
                'currencies_list' => $this->getPriceListHandler()->getPriceList()->getCurrencies(),
                'data' => $this->getPriceListHandler()->getPriceListSelectedCurrencies($priceList),
            ]
        );
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
                'data' => $this->getPriceListHandler()->getShowTierPrices(),
            ]
        );
    }

    /**
     * @return PriceListRequestHandler
     */
    protected function getPriceListHandler()
    {
        return $this->get('orob2b_pricing.model.price_list_request_handler');
    }
}

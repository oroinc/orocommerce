<?php

namespace OroB2B\Bundle\PricingBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Form\Form;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

class ProductController extends Controller
{
    /**
     * @Route("/sidebar", name="orob2b_pricing_frontend_product_sidebar")
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
        return $this->createForm(
            CurrencySelectionType::NAME,
            null,
            [
                'label' => 'orob2b.pricing.productprice.currency.label',
                'compact' => true,
                'required' => false,
                'empty_value' => false,
                'currencies_list' => $this->get('orob2b_pricing.model.price_list_tree_handler')->getPriceList($this->getUser())->getCurrencies(),
                'data' => null,
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
                'label' => 'orob2b.pricing.productprice.show_tier_prices.label',
                'required' => false,
                'data' => false,
            ]
        );
    }
}

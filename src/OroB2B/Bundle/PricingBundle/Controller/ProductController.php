<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Form\Form;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class ProductController extends Controller
{
    /**
     * @var PriceList
     */
    protected $defaultPriceList;

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
                'label' => 'orob2b.pricing.pricelist.entity_label',
            ]
        );
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
                'label' => false,
                'expanded' => true,
                'multiple' => true,
                'csrf_protection' => false,
                'currencies_list' => $this->getPriceListHandler()->getPriceList()->getCurrencies(),
                'data' => $this->getPriceListHandler()->getPriceListCurrencies(),
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
                'data' => $this->getPriceListHandler()->showTierPrices(),
            ]
        );
    }

    /**
     * @return PriceListRequestHandler
     */
    protected function getPriceListHandler()
    {
        return $this->get('orob2b_pricing.model.price_list_request_hanlder');
    }
}

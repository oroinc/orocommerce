<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Form\Form;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
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
            'showTierPrices' => $this->createShowTierPricesForm()->createView()
        ];
    }

    /**
     * @return Form
     */
    protected function createPriceListForm()
    {
        return $this->createForm(
            PriceListSelectType::NAME,
            $this->getDefaultPriceList(),
            [
                'create_enabled' => false,
                'empty_value' => false,
                'empty_data' => $this->getDefaultPriceList(),
                'configs' => ['allowClear' => false],
                'label' => 'orob2b.pricing.pricelist.entity_label'
            ]
        );
    }

    /**
     * @return PriceList
     */
    protected function getDefaultPriceList()
    {
        if (!$this->defaultPriceList) {
            /** @var PriceListRepository $repository */
            $repository = $this->getDoctrine()->getRepository(
                $this->container->getParameter('orob2b_pricing.entity.price_list.class')
            );

            $this->defaultPriceList = $repository->getDefault();
        }

        return $this->defaultPriceList;
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
                'currencies_list' => $this->getDefaultPriceList()->getCurrencies(),
                'data' => $this->getDefaultPriceList()->getCurrencies(),
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
            ['label' => 'orob2b.pricing.productprice.show_tier_prices.label', 'required' => false]
        );
    }
}

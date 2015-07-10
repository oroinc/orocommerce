<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Form\Form;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

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
        /** @var PriceListRepository $repository */
        $repository = $this->getDoctrine()->getRepository(
            $this->container->getParameter('orob2b_pricing.entity.price_list.class')
        );
        $defaultPriceList = $repository->getDefault();

        $priceListForm = $this->createForm(
            PriceListSelectType::NAME,
            $defaultPriceList,
            [
                'create_enabled' => false,
                'empty_value' => false,
                'empty_data' => $defaultPriceList,
                'configs' => ['allowClear' => false],
                'label' => false
            ]
        );

        return [
            'priceList' => $priceListForm->createView(),
            'tierPrice' => $this->createTierPriceForm()->createView()
        ];
    }

    /**
     * @return Form
     */
    protected function createTierPriceForm()
    {
        return $this->createForm(
            'checkbox',
            null,
            ['label' => 'orob2b.pricing.productprice.tier_price.label', 'required' => false]
        );
    }
}

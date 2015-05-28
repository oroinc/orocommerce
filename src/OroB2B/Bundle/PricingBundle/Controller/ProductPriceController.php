<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceController extends Controller
{
    /**
     * Create product form
     *
     * @Route(
     *      "/create/price-list/{priceListId}",
     *      name="orob2b_product_price_create_widget",
     *      requirements={"priceListId"="\d+"}
     * )
     * @Template("OroB2BPricingBundle:ProductPrice:widget/update.html.twig")
     * @Acl(
     *      id="orob2b_product_price_create",
     *      type="entity",
     *      class="OroB2BPricingBundle:ProductPrice",
     *      permission="CREATE"
     * )
     * @ParamConverter("priceList", class="OroB2BPricingBundle:ProductPrice", options={"id" = "priceListId"})
     *
     * @param PriceList $priceList
     * @return array|RedirectResponse
     */
    public function createAction(PriceList $priceList)
    {
        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        return $this->update($productPrice);
    }

    /**
     * Edit product form
     *
     * @Route("/update/{id}", name="orob2b_product_price_update_widget", requirements={"id"="\d+"})
     * @Template("OroB2BPricingBundle:ProductPrice:widget/update.html.twig")
     * @Acl(
     *      id="orob2b_product_price_update",
     *      type="entity",
     *      class="OroB2BPricingBundle:ProductPrice",
     *      permission="EDIT"
     * )
     * @param ProductPrice $productPrice
     * @return array|RedirectResponse
     */
    public function updateAction(ProductPrice $productPrice)
    {
        return $this->update($productPrice);
    }

    /**
     * @param ProductPrice $productPrice
     * @return array|RedirectResponse
     */
    protected function update(ProductPrice $productPrice)
    {
        $form = $this->createForm(PriceListProductPriceType::NAME, $productPrice);

        return $this->get('oro_form.model.update_handler')
            ->handleUpdate($productPrice, $form, null, null, null);
    }
}

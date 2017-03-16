<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;

class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @Route("/get-product-prices-by-customer", name="oro_pricing_price_by_customer")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductPricesByCustomer(Request $request)
    {
        return parent::getProductPricesByCustomer($request);
    }

    /**
     * Edit product form
     *
     * @Route(
     *     "/update/{priceList}/{id}",
     *     name="oro_product_price_update_widget",
     *     requirements={"id"="\d+", "priceListId"="\d+"}
     * )
     * @Template("OroPricingBundle:ProductPrice:widget/update.html.twig")
     * @Acl(
     *      id="oro_pricing_product_price_update",
     *      type="entity",
     *      class="OroPricingBundle:ProductPrice",
     *      permission="EDIT"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $priceList = $this->getDoctrine()->getRepository(PriceList::class)->find($request->get('priceList'));
        $prices = $this->getDoctrine()->getRepository(ProductPrice::class)
            ->findByPriceList(
                $this->get('oro_entity.query_hint_resolver'),
                $priceList,
                ['id' => $request->get('id')]
            );
        $productPrice = $prices[0];
        $form = $this->createForm(PriceListProductPriceType::NAME, $productPrice);

        return $this->get('oro_form.model.update_handler')
            ->handleUpdate($productPrice, $form, null, null, null);
    }

    /**
     * @Route("/get-matching-price", name="oro_pricing_matching_price")
     * @Method({"GET"})
     * @AclAncestor("oro_pricing_product_price_view")
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);
        $matchedPrices = $this->get('oro_pricing.provider.matching_price')->getMatchingPrices(
            $lineItems,
            $this->get('oro_pricing.model.price_list_request_handler')->getPriceListByCustomer()
        );

        return new JsonResponse($matchedPrices);
    }
}

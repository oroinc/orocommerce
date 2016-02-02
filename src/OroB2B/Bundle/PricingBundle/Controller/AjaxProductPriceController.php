<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListProductPriceType;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * Edit product form
     *
     * @Route("/update/{id}", name="orob2b_product_price_update_widget", requirements={"id"="\d+"})
     * @Template("OroB2BPricingBundle:ProductPrice:widget/update.html.twig")
     * @Acl(
     *      id="orob2b_pricing_product_price_update",
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
     * @Route("/get-product-prices-by-pricelist", name="orob2b_pricing_price_by_pricelist")
     * @Method({"GET"})
     * @AclAncestor("orob2b_pricing_product_price_view")
     *
     * {@inheritdoc}
     */
    public function getProductPricesByPriceListAction(Request $request)
    {
        return parent::getProductPricesByPriceListAction($request);
    }

    /**
     * @Route("/get-product-units-by-currency", name="orob2b_pricing_units_by_pricelist")
     * @Method({"GET"})
     * @AclAncestor("orob2b_pricing_product_price_view")
     *
     * {@inheritdoc}
     */
    public function getProductUnitsByCurrencyAction(Request $request)
    {
        /** @var BasePriceList $priceList */
        $priceList = $this->getEntityReference(
            $this->getParameter('orob2b_pricing.entity.price_list.class'),
            $request->get('price_list_id')
        );

        return $this->getProductUnitsByCurrency(
            $priceList,
            $request,
            $this->getParameter('orob2b_pricing.entity.product_price.class')
        );
    }

    /**
     * @Route("/get-matching-price", name="orob2b_pricing_matching_price")
     * @Method({"GET"})
     * @AclAncestor("orob2b_pricing_product_price_view")
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);
        $priceListId = null;
        /** @var CombinedPriceList|null $priceList */
        $priceList = null;
        if (!$priceListId) {
            $priceListId = $this->get('orob2b_pricing.model.frontend.price_list_request_handler')
                ->getPriceList()
                ->getId();
        }

        if ($priceListId) {
            $priceList = $this->getEntityReference(
                $this->getParameter('orob2b_pricing.entity.combined_price_list.class'),
                $priceListId
            );
        }

        $productsPriceCriteria = $this->prepareProductsPriceCriteria($lineItems);

        /** @var Price[] $matchedPrice */
        $matchedPrice = $this->get('orob2b_pricing.provider.combined_product_price')
            ->getMatchedPrices($productsPriceCriteria, $priceList);

        return new JsonResponse($this->formatMatchedPrices($matchedPrice));
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

    /**
     * @return PriceListRequestHandler
     */
    protected function getRequestHandler()
    {
        return $this->get('orob2b_pricing.model.price_list_request_handler');
    }

    /**
     * {@inheritdoc}
     */
    protected function getProductPriceProvider()
    {
        return $this->get('orob2b_pricing.provider.product_price');
    }
}

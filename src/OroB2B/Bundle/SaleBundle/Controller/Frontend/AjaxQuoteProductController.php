<?php

namespace OroB2B\Bundle\SaleBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class AjaxQuoteProductController extends Controller
{
    /**
     * @Route(
     *      "/match-offer/{id}",
     *      name="orob2b_sale_quote_frontend_quote_product_match_offer",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_sale_quote_frontend_view")
     *
     * @param QuoteProduct $quoteProduct
     * @param Request $request
     * @return JsonResponse
     */
    public function matchQuoteProductOfferAction(QuoteProduct $quoteProduct, Request $request)
    {
        $matcher = $this->get('orob2b_sale.service.quote_product_offer_matcher');
        $offer = $matcher->match($quoteProduct, $request->get('unit'), $request->get('qty'));

        return new JsonResponse(['offer' => !$offer ? null : $this->createResponseData($offer)]);
    }

    /**
     * @param QuoteProductOffer $offer
     * @return array
     */
    protected function createResponseData(QuoteProductOffer $offer)
    {
        $formatter = $this->get('oro_locale.formatter.number');
        $price = $offer->getPrice();

        return [
            'unit' => $offer->getProductUnitCode(),
            'qty' => $offer->getQuantity(),
            'price' => $formatter->formatCurrency($price->getValue(), $price->getCurrency())
        ];
    }
}

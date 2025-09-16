<?php

namespace Oro\Bundle\SaleBundle\Controller\Frontend;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Model\QuoteProductOfferMatcher;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Returns product offer by given quote product and quote demand via ajax.
 */
class AjaxQuoteProductController extends AbstractController
{
    /**
     *
     * @param QuoteProduct $quoteProduct
     * @param QuoteDemand $quoteDemand
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route(
        path: '/match-offer/{id}/{demandId}',
        name: 'oro_sale_quote_frontend_quote_product_match_offer',
        requirements: ['id' => '\d+', 'demandId' => '\d+']
    )]
    public function matchQuoteProductOfferAction(
        QuoteProduct $quoteProduct,
        #[MapEntity(id: 'demandId')]
        QuoteDemand $quoteDemand,
        Request $request
    ) {
        $authorizationChecker = $this->container->get('security.authorization_checker');
        if (!$authorizationChecker->isGranted('oro_sale_quote_demand_frontend_view', $quoteDemand) ||
            $quoteDemand->getQuote()->getId() !== $quoteProduct->getQuote()->getId()
        ) {
            throw $this->createAccessDeniedException();
        }

        $matcher = $this->container->get(QuoteProductOfferMatcher::class);
        $offer = $matcher->match($quoteProduct, $request->get('unit'), $request->get('qty'));

        return new JsonResponse($this->createResponseData($offer));
    }

    /**
     * @param QuoteProductOffer|null $offer
     * @return array
     */
    protected function createResponseData(?QuoteProductOffer $offer = null)
    {
        if (!$offer) {
            return [];
        }

        $price = $offer->getPrice();

        if (!$price) {
            return [];
        }

        $formatter = $this->container->get(NumberFormatter::class);

        return [
            'id' => $offer->getId(),
            'unit' => $offer->getProductUnitCode(),
            'qty' => $offer->getQuantity(),
            'price' => $formatter->formatCurrency($price->getValue(), $price->getCurrency()),
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                QuoteProductOfferMatcher::class,
                NumberFormatter::class,
            ]
        );
    }
}

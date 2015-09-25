<?php

namespace OroB2B\Bundle\SaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;

class QuoteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_sale_quote_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_sale_quote_view",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="VIEW"
     * )
     *
     * @param Quote $quote
     * @return array
     */
    public function viewAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }

    /**
     * @Route("/", name="orob2b_sale_quote_index")
     * @Template
     * @AclAncestor("orob2b_sale_quote_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_sale.entity.quote.class')
        ];
    }

    /**
     * @Route("/create", name="orob2b_sale_quote_create")
     * @Template("OroB2BSaleBundle:Quote:update.html.twig")
     * @Acl(
     *     id="orob2b_sale_quote_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroB2BSaleBundle:Quote"
     * )
     */
    public function createAction()
    {
        return $this->update(new Quote());
    }

    /**
     * @Route("/update/{id}", name="orob2b_sale_quote_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="orob2b_sale_quote_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroB2BSaleBundle:Quote"
     * )
     *
     * @param Quote $quote
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Quote $quote)
    {
        return $this->update($quote);
    }

    /**
     * @Route("/info/{id}", name="orob2b_sale_quote_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_sale_quote_view")
     *
     * @param Quote $quote
     * @return array
     */
    public function infoAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }

    /**
     * @param Quote $quote
     * @return array|RedirectResponse
     */
    protected function update(Quote $quote)
    {
        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');
        return $handler->handleUpdate(
            $quote,
            $this->createForm(QuoteType::NAME, $quote),
            function (Quote $quote) {
                return [
                    'route'         => 'orob2b_sale_quote_update',
                    'parameters'    => ['id' => $quote->getId()]
                ];
            },
            function (Quote $quote) {
                return [
                    'route'         => 'orob2b_sale_quote_view',
                    'parameters'    => ['id' => $quote->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.sale.controller.quote.saved.message'),
            null,
            function (Quote $quote, FormInterface $form, Request $request) {
                return [
                    'form' => $form->createView(),
                    'tierPrices' => $this->getTierPrices($quote),
                    'matchedPrices' => $this->getMatchedPrices($quote),
                ];
            }
        );
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function getTierPrices(Quote $quote)
    {
        $tierPrices = [];

        $productIds = $quote->getQuoteProducts()->filter(
            function (QuoteProduct $quoteProduct) {
                return $quoteProduct->getProduct() !== null || $quoteProduct->getProductReplacement() !== null;
            }
        )->map(
            function (QuoteProduct $quoteProduct) {
                if ($quoteProduct->getProductReplacement()) {
                    return $quoteProduct->getProductReplacement()->getId();
                } else {
                    return $quoteProduct->getProduct()->getId();
                }
            }
        );

        if ($productIds) {
            $tierPrices = $this->get('orob2b_pricing.provider.product_price')->getPriceByPriceListIdAndProductIds(
                $this->getPriceList($quote)->getId(),
                $productIds->toArray()
            );
        }

        return $tierPrices;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function getMatchedPrices(Quote $quote)
    {
        $matchedPrices = [];
        $productsPriceCriteria = $this->getProductsPriceCriteria($quote);

        if ($productsPriceCriteria) {
            $matchedPrices = $this->get('orob2b_pricing.provider.product_price')->getMatchedPrices(
                $productsPriceCriteria,
                $this->getPriceList($quote)
            );
        }

        /** @var Price $price */
        foreach ($matchedPrices as &$price) {
            if ($price) {
                $price = [
                    'value' => $price->getValue(),
                    'currency' => $price->getCurrency()
                ];
            }
        }

        return $matchedPrices;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function getProductsPriceCriteria(Quote $quote)
    {
        $productsPriceCriteria = [];

        /** @var QuoteProduct $quoteProduct */
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            if ($quoteProduct->getProductReplacement()) {
                $product = $quoteProduct->getProductReplacement();
            } elseif ($quoteProduct->getProduct()) {
                $product = $quoteProduct->getProduct();
            } else {
                continue;
            }

            /** @var QuoteProductOffer $quoteProductOffer */
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                if ($quoteProductOffer->getProductUnit() && $quoteProductOffer->getQuantity()) {
                    $productsPriceCriteria[] = new ProductPriceCriteria(
                        $product,
                        $quoteProductOffer->getProductUnit(),
                        $quoteProductOffer->getQuantity(),
                        $quoteProductOffer->getPrice()->getCurrency()
                    );
                }
            }
        }

        return $productsPriceCriteria;
    }

    /**
     * @param Quote $quote
     * @return PriceList
     */
    protected function getPriceList(Quote $quote)
    {
        $priceList = $quote->getPriceList();
        if (!$priceList) {
            $priceList = $this->get('orob2b_pricing.model.frontend.price_list_request_handler')->getPriceList();
        }
        return $priceList;
    }
}

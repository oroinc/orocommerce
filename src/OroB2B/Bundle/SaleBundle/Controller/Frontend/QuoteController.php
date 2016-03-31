<?php

namespace OroB2B\Bundle\SaleBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteDemandType;

class QuoteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_sale_quote_frontend_view", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="orob2b_sale_quote_frontend_view",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Quote $quote
     * @return array
     */
    public function viewAction(Quote $quote)
    {
        return [
            'data' => [
                'quote' => $quote
            ]
        ];
    }

    /**
     * @Route("/", name="orob2b_sale_quote_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="orob2b_sale_quote_frontend_index",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
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
     * @Route("/info/{id}", name="orob2b_sale_quote_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote/Frontend/widget:info.html.twig")
     * @AclAncestor("orob2b_sale_quote_frontend_view")
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
     * @Route("/choice/{id}", name="orob2b_sale_quote_frontend_choice", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="orob2b_sale_quote_frontend_choice",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Request $request
     * @param QuoteDemand $quoteDemand
     * @return array
     */
    public function choiceAction(Request $request, QuoteDemand $quoteDemand)
    {
        // TODO: Move to action_group
        if (!$quoteDemand->getDemandProducts()->count()) {
            foreach ($quoteDemand->getQuote()->getQuoteProducts() as $quoteProduct) {
                /** @var QuoteProductOffer $offer */
                $offer = $quoteProduct->getQuoteProductOffers()->first();
                $demandProduct = new QuoteProductDemand($quoteDemand, $offer, $offer->getQuantity());
                $quoteDemand->addDemandProduct($demandProduct);
            }
        }

        $form = $this->createForm(QuoteDemandType::NAME, $quoteDemand);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($quoteDemand);
            $em->flush();

            $actionGroupRegistry = $this->get('oro_action.action_group_registry');
            $actionGroup = $actionGroupRegistry->findByName('orob2b_sale_frontend_quote_accept_and_submit_to_order');
            if ($actionGroup) {
                $actionData = $actionGroup->execute(new ActionData(['data' => $quoteDemand]));

                $redirectUrl = $actionData->getRedirectUrl();
                if ($redirectUrl) {
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['redirectUrl' => $redirectUrl]);
                    } else {
                        return $this->redirect($redirectUrl);
                    }
                }
            }
        }

        return [
            'data' => [
                'data' => $quoteDemand,
                'form' => $form->createView(),
                'quote' => $quoteDemand->getQuote(),
                'totals' => (object)$this->getTotalProcessor()->getTotalWithSubtotalsAsArray($quoteDemand)
            ]
        ];
    }

    /**
     * @return TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
    }
}

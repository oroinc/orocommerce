<?php

namespace Oro\Bundle\SaleBundle\Controller\Frontend;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Form\Type\QuoteDemandType;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProviderInterface;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UIBundle\Tools\FlashMessageHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Frontend controller for quote management.
 */
class QuoteController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_sale_quote_frontend_view", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="oro_sale_quote_frontend_view",
     *      type="entity",
     *      class="OroSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Quote $quote
     * @return array
     */
    public function viewAction(Quote $quote)
    {
        if (!$quote->isAcceptable()) {
            $this->addFlash(
                'notice',
                $this->get(TranslatorInterface::class)->trans('oro.sale.controller.quote.expired.message')
            );
        }

        return [
            'data' => ['entity' => $quote]
        ];
    }

    /**
     * @Route(
     *     "/{guest_access_id}",
     *     name="oro_sale_quote_frontend_view_guest",
     *     requirements={
     *          "guest_access_id"="[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}"
     *     }
     * )
     * @Layout()
     * @ParamConverter(
     *     "quote",
     *     options={
     *          "repository_method": "getQuoteByGuestAccessId",
     *          "mapping": {"guest_access_id": "guestAccessId"},
     *          "map_method_signature" = true
     *     }
     * )
     */
    public function guestAccessAction(Quote $quote): array
    {
        $accessProvider = $this->get(GuestQuoteAccessProviderInterface::class);
        if (!$accessProvider->isGranted($quote)) {
            throw $this->createNotFoundException();
        }

        if (!$quote->isAcceptable()) {
            $this->addFlash(
                'notice',
                $this->get(TranslatorInterface::class)->trans('oro.sale.controller.quote.expired.message')
            );
        }

        return [
            'data' => ['entity' => $quote]
        ];
    }

    /**
     * @Route("/", name="oro_sale_quote_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="oro_sale_quote_frontend_index",
     *      type="entity",
     *      class="OroSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Quote::class
        ];
    }

    /**
     * @Route("/choice/{id}", name="oro_sale_quote_frontend_choice", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="oro_sale_quote_demand_frontend_view",
     *      type="entity",
     *      class="OroSaleBundle:QuoteDemand",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @param QuoteDemand $quoteDemand
     * @return array|Response
     */
    public function choiceAction(Request $request, QuoteDemand $quoteDemand)
    {
        $quote = $quoteDemand->getQuote();

        if (!$quote->isAcceptable()) {
            $this->get(FlashMessageHelper::class)
                ->addFlashMessage(
                    'info',
                    'oro.frontend.sale.message.quote.not_available',
                    ['%qid%' => $quote->getQid()]
                );

            return $this->redirectToRoute('oro_sale_quote_frontend_index');
        }

        $form = $this->createForm(QuoteDemandType::class, $quoteDemand);
        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $actionGroup = $this->get(ActionGroupRegistry::class)
                    ->findByName('oro_sale_frontend_quote_accept_and_submit_to_order');
                if ($actionGroup) {
                    $actionData = $actionGroup->execute(new ActionData(['data' => $quoteDemand]));

                    $this->getDoctrine()->getManagerForClass(QuoteDemand::class)->flush();

                    $redirectUrl = $actionData->getRedirectUrl();
                    if ($redirectUrl) {
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['redirectUrl' => $redirectUrl]);
                        }

                        return $this->redirect($redirectUrl);
                    }
                }
            }
        }

        return [
            'data' => [
                'data' => $quoteDemand,
                'form' => $form->createView(),
                'quote' => $quote,
                'totals' => (object)$this->getSubtotalsCalculator()->calculateSubtotals($quoteDemand)
            ]
        ];
    }

    /**
     * @Route("/subtotals/{id}", name="oro_sale_quote_frontend_subtotals", requirements={"id"="\d+"})
     * @Layout()
     * @AclAncestor("oro_sale_quote_demand_frontend_view")
     *
     * @param Request $request
     * @param QuoteDemand $quoteDemand
     * @return array
     */
    public function subtotalsAction(Request $request, QuoteDemand $quoteDemand)
    {
        $form = $this->createForm(QuoteDemandType::class, $quoteDemand);

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);
        }

        return [
            'data' => [
                'totals' => (object)$this->getSubtotalsCalculator()->calculateSubtotals($quoteDemand)
            ]
        ];
    }

    /**
     * @return QuoteDemandSubtotalsCalculatorInterface
     */
    protected function getSubtotalsCalculator()
    {
        return $this->get(QuoteDemandSubtotalsCalculatorInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                ActionGroupRegistry::class,
                GuestQuoteAccessProviderInterface::class,
                FlashMessageHelper::class,
                QuoteDemandSubtotalsCalculatorInterface::class
            ]
        );
    }
}

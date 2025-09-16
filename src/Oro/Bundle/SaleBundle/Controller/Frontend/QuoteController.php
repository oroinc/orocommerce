<?php

namespace Oro\Bundle\SaleBundle\Controller\Frontend;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Form\Type\QuoteDemandType;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProviderInterface;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;
use Oro\Bundle\SaleBundle\Workflow\ActionGroup\AcceptQuoteAndSubmitToOrder;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Tools\FlashMessageHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Frontend controller for quote management.
 */
class QuoteController extends AbstractController
{
    /**
     * @param Quote $quote
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_sale_quote_frontend_view', requirements: ['id' => '\d+'])]
    #[Layout]
    #[Acl(
        id: 'oro_sale_quote_frontend_view',
        type: 'entity',
        class: Quote::class,
        permission: 'VIEW',
        groupName: 'commerce'
    )]
    public function viewAction(
        #[MapEntity(expr: 'repository.getQuote(id)')]
        Quote $quote
    ): array {
        if (!$quote->isAcceptable()) {
            $this->addFlash(
                'notice',
                $this->container->get(TranslatorInterface::class)->trans('oro.sale.controller.quote.expired.message')
            );
        }

        return [
            'data' => [
                'entity' => $quote,
                'grid_name' => 'frontend-quotes-line-items-grid'
            ],
        ];
    }

    #[Route(
        path: '/{guest_access_id}',
        name: 'oro_sale_quote_frontend_view_guest',
        requirements: [
            'guest_access_id' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}'
        ]
    )]
    #[Layout]
    public function guestAccessAction(
        #[MapEntity(
            expr: 'repository.getQuoteByGuestAccessId(guest_access_id)',
            mapping: ['guest_access_id' => 'guestAccessId']
        )]
        Quote $quote
    ): array {
        $accessProvider = $this->container->get(GuestQuoteAccessProviderInterface::class);
        if (!$accessProvider->isGranted($quote)) {
            throw $this->createNotFoundException();
        }

        if (!$quote->isAcceptable()) {
            $this->addFlash(
                'notice',
                $this->container->get(TranslatorInterface::class)->trans('oro.sale.controller.quote.expired.message')
            );
        }

        return [
            'data' => [
                'entity' => $quote,
                'grid_name' => 'guest-frontend-quotes-line-items-grid'
            ],
        ];
    }

    /**
     * @return array
     */
    #[Route(path: '/', name: 'oro_sale_quote_frontend_index')]
    #[Layout(vars: ['entity_class'])]
    #[Acl(
        id: 'oro_sale_quote_frontend_index',
        type: 'entity',
        class: Quote::class,
        permission: 'VIEW',
        groupName: 'commerce'
    )]
    public function indexAction()
    {
        return [
            'entity_class' => Quote::class
        ];
    }

    /**
     *
     * @param Request $request
     * @param QuoteDemand $quoteDemand
     * @return array|Response
     */
    #[Route(path: '/choice/{id}', name: 'oro_sale_quote_frontend_choice', requirements: ['id' => '\d+'])]
    #[Layout]
    #[Acl(
        id: 'oro_sale_quote_demand_frontend_view',
        type: 'entity',
        class: QuoteDemand::class,
        permission: 'VIEW',
        groupName: 'commerce'
    )]
    public function choiceAction(Request $request, QuoteDemand $quoteDemand)
    {
        $quote = $quoteDemand->getQuote();

        if (!$quote->isAcceptable()) {
            $this->container->get(FlashMessageHelper::class)
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
                $actionResult = $this->container->get(AcceptQuoteAndSubmitToOrder::class)->execute($quoteDemand);
                $this->container->get('doctrine')->getManagerForClass(QuoteDemand::class)->flush();

                $redirectUrl = $actionResult['redirectUrl'] ?? null;
                if ($redirectUrl) {
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['redirectUrl' => $redirectUrl]);
                    }

                    return $this->redirect($redirectUrl);
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
     *
     * @param Request $request
     * @param QuoteDemand $quoteDemand
     * @return array
     */
    #[Route(path: '/subtotals/{id}', name: 'oro_sale_quote_frontend_subtotals', requirements: ['id' => '\d+'])]
    #[Layout]
    #[AclAncestor('oro_sale_quote_demand_frontend_view')]
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
        return $this->container->get(QuoteDemandSubtotalsCalculatorInterface::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                GuestQuoteAccessProviderInterface::class,
                FlashMessageHelper::class,
                QuoteDemandSubtotalsCalculatorInterface::class,
                'doctrine' => ManagerRegistry::class,
                AcceptQuoteAndSubmitToOrder::class
            ]
        );
    }
}

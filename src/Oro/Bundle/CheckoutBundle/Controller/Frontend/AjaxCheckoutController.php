<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManager;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles logic of checkout ajax requests.
 */
class AjaxCheckoutController extends AbstractController
{
    #[Route(
        path: '/get-totals-for-checkout/{entityId}',
        name: 'oro_checkout_frontend_totals',
        requirements: ['entityId' => '\d+']
    )]
    #[AclAncestor('oro_checkout_frontend_checkout')]
    public function getTotalsAction(Request $request, int $entityId): JsonResponse
    {
        /** @var Checkout|null $checkout */
        $checkout = $this->container->get('doctrine')->getRepository(Checkout::class)
            ->getCheckoutWithRelations($entityId);
        if (!$checkout) {
            return new JsonResponse('', Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('EDIT', $checkout);

        $this->setCorrectCheckoutShippingMethodData($checkout, $request);

        return new JsonResponse($this->container->get(CheckoutTotalsProvider::class)->getTotalsArray($checkout));
    }

    private function setCorrectCheckoutShippingMethodData(Checkout $checkout, Request $request): void
    {
        $workflowTransitionData = $request->request->all('oro_workflow_transition');
        if (!\array_key_exists('shipping_method', $workflowTransitionData)
            || !\array_key_exists('shipping_method_type', $workflowTransitionData)
        ) {
            return;
        }

        if (isset($workflowTransitionData['line_items_shipping_methods'])) {
            $this->container->get(CheckoutLineItemsShippingManager::class)
                ->updateLineItemsShippingMethods(
                    $this->decodeShippingMethods($workflowTransitionData['line_items_shipping_methods']),
                    $checkout
                );
        }

        if (isset($workflowTransitionData['line_item_groups_shipping_methods'])) {
            $this->container->get(CheckoutLineItemGroupsShippingManager::class)
                ->updateLineItemGroupsShippingMethods(
                    $this->decodeShippingMethods($workflowTransitionData['line_item_groups_shipping_methods']),
                    $checkout
                );
        }

        $checkout
            ->setShippingMethod($workflowTransitionData['shipping_method'])
            ->setShippingMethodType($workflowTransitionData['shipping_method_type']);
    }

    private function decodeShippingMethods(string $val): array
    {
        return json_decode($val, true, 512, JSON_THROW_ON_ERROR);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                CheckoutTotalsProvider::class,
                CheckoutLineItemsShippingManager::class,
                CheckoutLineItemGroupsShippingManager::class,
                'doctrine' => ManagerRegistry::class
            ]
        );
    }
}

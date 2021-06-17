<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction\AddLineItemMassActionProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for getting mass actions for datagrid
 */
class AjaxMassActionController extends AbstractController
{
    /**
     * Get grid mass actions
     *
     * @Route(
     *      "/get-mass-actions",
     *      name="oro_shopping_list_frontend_get_mass_actions",
     * )
     *
     * @return JsonResponse
     */
    public function getMassActionsAction()
    {
        $massActionProvider = $this->getMassActionProvider();
        $formattedMassActions = $massActionProvider->getFormattedActions();

        return new JsonResponse($formattedMassActions);
    }

    private function getMassActionProvider(): AddLineItemMassActionProvider
    {
        return $this->get(AddLineItemMassActionProvider::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                AddLineItemMassActionProvider::class,
            ]
        );
    }
}

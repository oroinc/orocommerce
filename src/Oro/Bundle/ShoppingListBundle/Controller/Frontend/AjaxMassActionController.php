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

    /**
     * @return AddLineItemMassActionProvider
     */
    private function getMassActionProvider()
    {
        return $this->get('oro_shopping_list.action.datagrid.mass_action_provider');
    }
}

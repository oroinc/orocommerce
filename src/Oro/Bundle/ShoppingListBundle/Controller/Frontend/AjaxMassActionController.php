<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction\AddLineItemMassActionProvider;

class AjaxMassActionController extends Controller
{
    /**
     * Get grid mass actions
     *
     * @Route(
     *      "/get-mass-actions",
     *      name="oro_shopping_list_frontend_get_mass_actions",
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
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

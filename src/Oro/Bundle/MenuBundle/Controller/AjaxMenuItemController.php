<?php

namespace Oro\Bundle\MenuBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class AjaxMenuItemController extends Controller
{
    /**
     * @Route("/move", name="oro_menu_item_move")
     * @Method({"PUT"})
     * @AclAncestor("oro_menu_item_update")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function categoryMoveAction(Request $request)
    {
        $nodeId = $request->get('id');
        $parentId = $request->get('parent');
        $position = $request->get('position');

        return new JsonResponse(
            $this->get('oro_menu.tree.menu_item_tree_handler')->moveNode($nodeId, $parentId, $position)
        );
    }
}

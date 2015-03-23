<?php

namespace OroB2B\Bundle\CMSBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class AjaxPageController extends Controller
{
    /**
     * @Route(
     *      "/page-move",
     *      name="orob2b_cms_page_move"
     * )
     * @Method({"PUT"})
     * @AclAncestor("orob2b_cms_page_update")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pageMoveAction(Request $request)
    {
        $nodeId   = (int)$request->get('id');
        $parentId = (int)$request->get('parent');
        $position = (int)$request->get('position');

        return new JsonResponse(
            $this->get('orob2b_cms.page_tree_handler')->movePage($nodeId, $parentId, $position)
        );
    }
}

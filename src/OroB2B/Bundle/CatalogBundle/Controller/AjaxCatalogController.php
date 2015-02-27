<?php

namespace OroB2B\Bundle\CatalogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class AjaxCatalogController extends Controller
{
    /**
     * @Route("/category-list/{selectedCategoryId}", name="orob2b_category_list")
     * @AclAncestor("orob2b_category_view")
     *
     * @param int $selectedCategoryId
     * @return array
     */
    public function categoryListAction($selectedCategoryId)
    {
        return new JsonResponse($this->get('orob2b_catalog.category_tree_handler')->createTree($selectedCategoryId));
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AjaxGetProductsCountController extends AbstractController
{
    /**
     * @Route(
     *      "/get-count/{gridName}",
     *      name="oro_product_datagrid_count_get",
     *      requirements={"gridName"="[\w\:-]+"}
     * )
     *
     * @param string $gridName
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction($gridName, Request $request)
    {
        $params = $request->get('params', []);
        $count = $this->get('oro_product.provider.grid_count_provider')->getGridCount($gridName, $params);

        return new JsonResponse($count);
    }
}

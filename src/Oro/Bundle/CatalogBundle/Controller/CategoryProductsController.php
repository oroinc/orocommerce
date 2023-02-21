<?php

namespace Oro\Bundle\CatalogBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides the ability to control of the Products sortOrder in Category
 */
class CategoryProductsController extends AbstractController
{
    /**
     * @Route(
     *     "/manage-sort-order/widget",
     *     name="oro_catalog_category_products_manage_sort_order_widget",
     *     methods={"GET"}
     * )
     * @AclAncestor("oro_catalog_category_update")
     * @Template
     */
    public function manageSortOrderWidgetAction(Request $request): array
    {
        return [
            'params' => $request->get('params', []),
            'renderParams' => $this->getRenderParams($request),
            'multiselect' => (bool)$request->get('multiselect', false),
        ];
    }

    private function getRenderParams(Request $request): array
    {
        $renderParams = $request->get('renderParams', []);
        $renderParamsTypes = $request->get('renderParamsTypes', []);

        foreach ($renderParamsTypes as $param => $type) {
            if (array_key_exists($param, $renderParams)) {
                switch ($type) {
                    case 'bool':
                    case 'boolean':
                        $renderParams[$param] = (bool)$renderParams[$param];
                        break;
                    case 'int':
                    case 'integer':
                        $renderParams[$param] = (int)$renderParams[$param];
                        break;
                }
            }
        }

        return $renderParams;
    }
}

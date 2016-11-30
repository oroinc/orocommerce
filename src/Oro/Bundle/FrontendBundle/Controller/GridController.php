<?php

namespace Oro\Bundle\FrontendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\LayoutBundle\Annotation\Layout;

class GridController extends Controller
{
    const EXPORT_BATCH_SIZE = 200;

    /**
     * @Route(
     *      "/datagrid/widget/{gridName}",
     *      name="oro_frontend_datagrid_widget",
     *      requirements={"gridName"="[\w\:-]+"}
     * )
     * @Layout(vars={"gridName", "params", "renderParams", "multiselect"})
     *
     * @param string  $gridName
     * @param Request $request
     * @return Response
     */
    public function widgetAction($gridName, Request $request)
    {
        return [
            'gridName' => $gridName,
            'params' => $request->get('params', []),
            'renderParams' => $this->getRenderParams($request),
            'multiselect' => (bool)$request->get('multiselect', false),
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getRenderParams(Request $request)
    {
        $renderParams      = $request->get('renderParams', []);
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

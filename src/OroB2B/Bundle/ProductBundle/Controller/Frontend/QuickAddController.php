<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;

class QuickAddController extends Controller
{
    /**
     * @Route("/", name="orob2b_product_frontend_quick_add")
     * @Layout
     *
     * @param Request $request
     * @return array|Response
     */
    public function addAction(Request $request)
    {
        $response = $this->get('orob2b_product.form_handler.quick_add')->process(
            $request,
            'orob2b_product_frontend_quick_add'
        );

        return $response ?: [];
    }

    /**
     * @Route("/import/", name="orob2b_product_frontend_quick_add_import")
     * @Layout(vars={"import_step"})
     *
     * @param Request $request
     * @return array|Response
     */
    public function importAction(Request $request)
    {
        $collection = $this->get('orob2b_product.form_handler.quick_add')->processImport($request);
        return [
            'import_step' => $collection === null ? 'form' : 'result',
            'data' => [
                'collection' => $collection,
                'backToUrl' => $request->getUri(),
            ]
        ];
    }

    /**
     * @Route("/copy-paste/", name="orob2b_product_frontend_quick_add_copy_paste")
     * @Layout(vars={"import_step"})
     *
     * @param Request $request
     * @return array
     */
    public function copyPasteAction(Request $request)
    {
        $collection = $this->get('orob2b_product.form_handler.quick_add')->processCopyPaste($request);
        return [
            'import_step' => $collection === null ? 'form' : 'result',
            'data' => [
                'collection' => $collection,
            ]
        ];
    }

    /**
     * @Route("/validation/result/", name="orob2b_product_frontend_quick_add_validation_result")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validationResultAction(Request $request)
    {
        $response = $this->get('orob2b_product.form_handler.quick_add')->process(
            $request,
            'orob2b_product_frontend_quick_add'
        );

        if (!$response instanceof RedirectResponse) {
            return new JsonResponse([
                'redirectUrl' => $this->generateUrl('orob2b_product_frontend_quick_add')
            ]);
        }

        return new JsonResponse([
            'redirectUrl' => $response->getTargetUrl()
        ]);
    }
}

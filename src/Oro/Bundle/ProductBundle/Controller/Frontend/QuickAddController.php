<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @AclAncestor("oro_quick_add_form")
 */
class QuickAddController extends AbstractController
{
    /**
     * @Route("/", name="oro_product_frontend_quick_add")
     * @Layout
     *
     * @param Request $request
     * @return array|Response
     */
    public function addAction(Request $request)
    {
        $response = $this->get('oro_product.form_handler.quick_add')->process(
            $request,
            'oro_product_frontend_quick_add'
        );

        return $response ?: [];
    }

    /**
     * @Route("/import/", name="oro_product_frontend_quick_add_import")
     * @Layout(vars={"import_step", "method"})
     *
     * @param Request $request
     * @return array|Response
     */
    public function importAction(Request $request)
    {
        $collection = $this->get('oro_product.layout.data_provider.quick_add_collection')->processImport();

        return [
            'import_step' => $this->getImportStep($collection),
            'method' => $request->getMethod(),
            'data' => [
                'collection' => $collection,
                'backToUrl' => $request->getUri(),
            ]
        ];
    }

    /**
     * @Route("/copy-paste/", name="oro_product_frontend_quick_add_copy_paste")
     * @Layout(vars={"import_step"})
     *
     * @return array
     */
    public function copyPasteAction()
    {
        $collection = $this->get('oro_product.layout.data_provider.quick_add_collection')->processCopyPaste();

        return [
            'import_step' => $collection === null ? 'form' : 'result',
            'data' => [
                'collection' => $collection,
            ]
        ];
    }

    /**
     * @Route("/validation/result/", name="oro_product_frontend_quick_add_validation_result")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validationResultAction(Request $request)
    {
        $response = $this->get('oro_product.form_handler.quick_add')->process(
            $request,
            'oro_product_frontend_quick_add'
        );

        if (!$response instanceof RedirectResponse) {
            return new JsonResponse([
                'redirectUrl' => $this->generateUrl('oro_product_frontend_quick_add')
            ]);
        }

        return new JsonResponse([
            'redirectUrl' => $response->getTargetUrl()
        ]);
    }

    /**
     * @param QuickAddRowCollection|null $collection
     * @return string
     */
    private function getImportStep(QuickAddRowCollection $collection = null)
    {
        if ($collection !== null && !$collection->isEmpty() && $collection->hasValidRows()) {
            return 'result';
        }

        return 'form';
    }
}

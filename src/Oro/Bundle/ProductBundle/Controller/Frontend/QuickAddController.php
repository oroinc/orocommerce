<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\QuickAdd\QuickAddImportResultsProviderInterface;
use Oro\Bundle\ProductBundle\Provider\QuickAddCollectionProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serves Quick Add actions.
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
        $response = $this->get(QuickAddHandler::class)->process(
            $request,
            'oro_product_frontend_quick_add'
        );

        return $response ?: [];
    }

    /**
     * @Route("/import/", name="oro_product_frontend_quick_add_import")
     */
    public function importAction(): Response
    {
        /** @var QuickAddRowCollection|null $collection */
        $collection = $this->get(QuickAddCollectionProvider::class)->processImport();
        $form = $this->get(ProductFormProvider::class)->getQuickAddImportForm();
        $isValid = $form->isSubmitted() && $form->isValid();

        $response = [
            'success' => $isValid,
            'data' => [
                'products' => $isValid
                    ? $this->get(QuickAddImportResultsProviderInterface::class)->getResults($collection)
                    : [],
            ],
        ];

        if (!$isValid) {
            foreach ($form->getErrors(true) as $formError) {
                $response['messages']['error'][] = $formError->getMessage();
            }
        } elseif (!$collection->hasValidRows()) {
            $response['messages']['error'][] = $this->get(TranslatorInterface::class)
                ->trans('oro.product.frontend.quick_add.import_validation.empty_file.error.message');
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/copy-paste/", name="oro_product_frontend_quick_add_copy_paste")
     * @Layout(vars={"import_step"})
     *
     * @return array
     */
    public function copyPasteAction()
    {
        $collection = $this->get(QuickAddCollectionProvider::class)->processCopyPaste();

        return [
            'import_step' => $collection === null ? 'form' : 'result',
            'data' => [
                'collection' => $collection,
            ],
        ];
    }

    /**
     * @Route("/import/help", name="oro_product_frontend_quick_add_import_help")
     * @Layout
     */
    public function getImportHelpAction(): array
    {
        return [];
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                QuickAddHandler::class,
                QuickAddCollectionProvider::class,
                QuickAddImportResultsProviderInterface::class,
                ProductFormProvider::class,
            ]
        );
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddImportFromFileHandler;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddProcessHandler;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\ProductBundle\Provider\QuickAddCollectionProvider;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        $form = $this->get(ProductFormProvider::class)->getQuickAddForm();

        return $this->get(QuickAddProcessHandler::class)->process($form, $request);
    }

    /**
     * @Route("/import/", name="oro_product_frontend_quick_add_import")
     */
    public function importAction(Request $request)
    {
        $form = $this->get(ProductFormProvider::class)->getQuickAddImportForm();

        return $this->get(QuickAddImportFromFileHandler::class)->process($form, $request);
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
                QuickAddProcessHandler::class,
                QuickAddImportFromFileHandler::class,
                QuickAddCollectionProvider::class,
                QuickAddCollectionNormalizerInterface::class,
                ProductFormProvider::class,
            ]
        );
    }
}

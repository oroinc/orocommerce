<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddImportFromFileHandler;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddImportFromPlainTextHandler;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddProcessHandler;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for the quick order form page.
 *
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
    public function addAction(Request $request): Response|array
    {
        $form = $this->get(ProductFormProvider::class)->getQuickAddForm();

        return $this->get(QuickAddProcessHandler::class)->process($form, $request);
    }

    /**
     * @Route("/import/", name="oro_product_frontend_quick_add_import")
     */
    public function importAction(Request $request): Response
    {
        $form = $this->get(ProductFormProvider::class)->getQuickAddImportForm();

        return $this->get(QuickAddImportFromFileHandler::class)->process($form, $request);
    }

    /**
     * @Route("/copy-paste/", name="oro_product_frontend_quick_add_copy_paste")
     */
    public function copyPasteAction(Request $request): Response
    {
        $form = $this->get(ProductFormProvider::class)->getQuickAddCopyPasteForm();

        return $this->get(QuickAddImportFromPlainTextHandler::class)->process($form, $request);
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
                ProductFormProvider::class,
                QuickAddProcessHandler::class,
                QuickAddImportFromFileHandler::class,
                QuickAddImportFromPlainTextHandler::class,
            ]
        );
    }
}

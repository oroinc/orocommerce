<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddImportFromFileHandler;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddImportFromPlainTextHandler;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddProcessHandler;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for the quick order form page.
 */
#[AclAncestor('oro_quick_add_form')]
class QuickAddController extends AbstractController
{
    /**
     *
     * @param Request $request
     * @return array|Response
     */
    #[Route(path: '/', name: 'oro_product_frontend_quick_add')]
    #[Layout]
    public function addAction(Request $request): Response|array
    {
        $form = $this->container->get(ProductFormProvider::class)->getQuickAddForm();

        return $this->container->get(QuickAddProcessHandler::class)->process($form, $request);
    }

    #[Route(path: '/import/', name: 'oro_product_frontend_quick_add_import')]
    public function importAction(Request $request): Response
    {
        $form = $this->container->get(ProductFormProvider::class)->getQuickAddImportForm();

        return $this->container->get(QuickAddImportFromFileHandler::class)->process($form, $request);
    }

    #[Route(path: '/copy-paste/', name: 'oro_product_frontend_quick_add_copy_paste')]
    public function copyPasteAction(Request $request): Response
    {
        $form = $this->container->get(ProductFormProvider::class)->getQuickAddCopyPasteForm();

        return $this->container->get(QuickAddImportFromPlainTextHandler::class)->process($form, $request);
    }

    #[Route(path: '/import/help', name: 'oro_product_frontend_quick_add_import_help')]
    #[Layout]
    public function getImportHelpAction(): array
    {
        return [];
    }

    #[\Override]
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

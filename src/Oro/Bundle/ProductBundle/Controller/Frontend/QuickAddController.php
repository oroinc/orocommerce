<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddImportFromFileHandler;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddImportFromPlainTextHandler;
use Oro\Bundle\ProductBundle\Form\Handler\QuickAddProcessHandler;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\QuickAddCollectionProvider;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    public function addAction(Request $request)
    {
        if ($this->isOptimizedFormEnabled()) {
            $form = $this->get(ProductFormProvider::class)->getQuickAddForm();

            return $this->get(QuickAddProcessHandler::class)->process($form, $request);
        }

        return $this->get(QuickAddHandler::class)->process($request, 'oro_product_frontend_quick_add') ?: [];
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
        if ($this->isOptimizedFormEnabled()) {
            $form = $this->get(ProductFormProvider::class)->getQuickAddImportForm();

            return $this->get(QuickAddImportFromFileHandler::class)->process($form, $request);
        }

        $collection = $this->get(QuickAddCollectionProvider::class)->processImport();

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
        if ($this->isOptimizedFormEnabled()) {
            $form = $this->get(ProductFormProvider::class)->getQuickAddCopyPasteForm();

            return $this->get(QuickAddImportFromPlainTextHandler::class)->process(
                $form,
                $this->get(RequestStack::class)->getMasterRequest()
            );
        }

        $collection = $this->get(QuickAddCollectionProvider::class)->processCopyPaste();

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
        $response = $this->get(QuickAddHandler::class)->process(
            $request,
            'oro_product_frontend_quick_add'
        );

        if (!$response instanceof RedirectResponse) {
            return new JsonResponse([
                'redirectUrl' => $this->generateUrl('oro_product_frontend_quick_add'),
            ]);
        }

        return new JsonResponse([
            'redirectUrl' => $response->getTargetUrl(),
        ]);
    }

    /**
     * @Route("/import/help", name="oro_product_frontend_quick_add_import_help")
     * @Layout
     */
    public function getImportHelpAction(): array
    {
        return [];
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

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                QuickAddHandler::class,
                QuickAddProcessHandler::class,
                QuickAddImportFromFileHandler::class,
                QuickAddImportFromPlainTextHandler::class,
                QuickAddCollectionProvider::class,
                QuickAddCollectionNormalizerInterface::class,
                ProductFormProvider::class,
                ConfigManager::class,
                RequestStack::class,
            ]
        );
    }

    protected function isOptimizedFormEnabled(): bool
    {
        return (bool)($this->get(ConfigManager::class)->get(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED)
        ) ?? false);
    }
}

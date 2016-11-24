<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Box\Spout\Common\Exception\UnsupportedTypeException;

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddHandler
{
    /**
     * @var ProductFormProvider
     */
    protected $productFormProvider;

    /**
     * @var QuickAddRowCollectionBuilder
     */
    protected $quickAddRowCollectionBuilder;

    /**
     * @var ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ProductFormProvider $productFormProvider
     * @param QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder
     * @param ComponentProcessorRegistry $componentRegistry
     * @param UrlGeneratorInterface $router
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ProductFormProvider $productFormProvider,
        QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder,
        ComponentProcessorRegistry $componentRegistry,
        UrlGeneratorInterface $router,
        TranslatorInterface $translator
    ) {
        $this->productFormProvider = $productFormProvider;
        $this->quickAddRowCollectionBuilder = $quickAddRowCollectionBuilder;
        $this->componentRegistry = $componentRegistry;
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @param string $successDefaultRoute
     * @return Response|null|false
     */
    public function process(Request $request, $successDefaultRoute)
    {
        $response = null;
        if (!$request->isMethod(Request::METHOD_POST)) {
            return $response;
        }

        $processor = $this->getProcessor($this->getComponentName($request));

        $options = [];
        $collection = $this->quickAddRowCollectionBuilder->buildFromRequest($request);
        $options['products'] = $collection->getProducts();
        if ($processor) {
            $options['validation_required'] = $processor->isValidationRequired();
        }

        $form = $this->productFormProvider->getQuickAddForm([], $options);
        $form->submit($request);

        if (!$processor || !$processor->isAllowed()) {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add(
                'error',
                $this->translator->trans('oro.product.frontend.quick_add.messages.component_not_accessible')
            );
        } elseif ($form->isValid()) {
            $products = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getData();
            $products = array_map(
                function (ProductRow $productRow) {
                    return [
                        ProductDataStorage::PRODUCT_SKU_KEY => $productRow->productSku,
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => $productRow->productQuantity
                    ];
                },
                $products
            );

            $additionalData = $request->get(
                QuickAddType::NAME . '[' . QuickAddType::ADDITIONAL_FIELD_NAME . ']',
                null,
                true
            );
            $response = $processor->process(
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products,
                    ProductDataStorage::ADDITIONAL_DATA_KEY => $additionalData,
                ],
                $request
            );

            if ($response === false) {
                return $response;
            } elseif (!$response) {
                $response = new RedirectResponse($this->router->generate($successDefaultRoute));
            }
        }

        return $response;
    }

    /**
     * @param Request $request
     * @return QuickAddRowCollection|null
     */
    public function processImport(Request $request)
    {
        $form = $this->productFormProvider->getQuickAddImportForm()->handleRequest($request);
        $collection = null;

        if ($form->isValid()) {
            $file = $form->get(QuickAddImportFromFileType::FILE_FIELD_NAME)->getData();
            try {
                $collection = $this->quickAddRowCollectionBuilder->buildFromFile($file);
                $this->productFormProvider->getQuickAddForm($collection->getFormData());
            } catch (UnsupportedTypeException $e) {
                $form->get(QuickAddImportFromFileType::FILE_FIELD_NAME)->addError(new FormError(
                    $this->translator->trans(
                        'oro.product.frontend.quick_add.invalid_file_type',
                        [],
                        'validators'
                    )
                ));
            }
        }

        return $collection;
    }

    /**
     * @param Request $request
     * @return QuickAddRowCollection|null
     */
    public function processCopyPaste(Request $request)
    {
        $form = $this->productFormProvider->getQuickAddCopyPasteForm()->handleRequest($request);
        $collection = null;

        if ($form->isValid()) {
            $copyPasteText = $form->get(QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME)->getData();
            $collection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($copyPasteText);
            $this->productFormProvider->getQuickAddForm($collection->getFormData());
        }

        return $collection;
    }

    /**
     * @param Request $request
     * @return null|string
     */
    protected function getComponentName(Request $request)
    {
        $formData = $request->get(QuickAddType::NAME, []);

        $name = null;
        if (array_key_exists(QuickAddType::COMPONENT_FIELD_NAME, $formData)) {
            $name = $formData[QuickAddType::COMPONENT_FIELD_NAME];
        }

        return $name;
    }

    /**
     * @param string $name
     * @return null|ComponentProcessorInterface
     */
    protected function getProcessor($name)
    {
        return $this->componentRegistry->getProcessorByName($name);
    }
}

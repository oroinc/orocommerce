<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use Box\Spout\Common\Exception\UnsupportedTypeException;

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Model\ProductRow;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Layout\DataProvider\ProductFormDataProvider;
use OroB2B\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddHandler
{
    /**
     * @var ProductFormDataProvider
     */
    protected $productFormDataProvider;

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
     * @param ProductFormDataProvider $productFormDataProvider
     * @param QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder
     * @param ComponentProcessorRegistry $componentRegistry
     * @param UrlGeneratorInterface $router
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ProductFormDataProvider $productFormDataProvider,
        QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder,
        ComponentProcessorRegistry $componentRegistry,
        UrlGeneratorInterface $router,
        TranslatorInterface $translator
    ) {
        $this->productFormDataProvider = $productFormDataProvider;
        $this->quickAddRowCollectionBuilder = $quickAddRowCollectionBuilder;
        $this->componentRegistry = $componentRegistry;
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @param string $successDefaultRoute
     * @return Response|null
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

        $form = $this->productFormDataProvider->getBaseQuickAddForm([], $options)->getForm();
        $form->submit($request);

        if (!$processor || !$processor->isAllowed()) {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add(
                'error',
                $this->translator->trans('orob2b.product.frontend.quick_add.messages.component_not_accessible')
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
            if (!$response) {
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
        $form = $this->productFormDataProvider->getQuickAddImportForm()->getForm()->handleRequest($request);
        $collection = null;

        if ($form->isValid()) {
            $file = $form->get(QuickAddImportFromFileType::FILE_FIELD_NAME)->getData();
            try {
                $collection = $this->quickAddRowCollectionBuilder->buildFromFile($file);
                $this->productFormDataProvider->getBaseQuickAddForm($collection->getFormData())->getForm();
            } catch (UnsupportedTypeException $e) {
                $form->get(QuickAddImportFromFileType::FILE_FIELD_NAME)->addError(new FormError(
                    $this->translator->trans(
                        'orob2b.product.frontend.quick_add.invalid_file_type',
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
        $form = $this->productFormDataProvider->getQuickAddCopyPasteForm()->getForm()->handleRequest($request);
        $collection = null;

        if ($form->isValid()) {
            $copyPasteText = $form->get(QuickAddCopyPasteType::COPY_PASTE_FIELD_NAME)->getData();
            $collection = $this->quickAddRowCollectionBuilder->buildFromCopyPasteText($copyPasteText);
            $this->productFormDataProvider->getBaseQuickAddForm($collection->getFormData())->getForm();
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

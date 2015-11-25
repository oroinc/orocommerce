<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddHandler
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /** @var  ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $productClass;

    /**
     * @param TranslatorInterface $translator
     * @param FormFactoryInterface $formFactory
     * @param ComponentProcessorRegistry $componentRegistry
     * @param ManagerRegistry $registry
     */
    public function __construct(
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        ComponentProcessorRegistry $componentRegistry,
        ManagerRegistry $registry
    ) {
        $this->translator = $translator;
        $this->formFactory = $formFactory;
        $this->componentRegistry = $componentRegistry;
        $this->registry = $registry;
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @param Request $request
     * @return array ['form' => FormInterface, 'response' => Response|null]
     */
    public function process(Request $request)
    {
        $response = null;
        $formOptions = [];

        $processor = $this->getProcessor($this->getComponentName($request));
        if ($processor) {
            $formOptions['validation_required'] = $processor->isValidationRequired();
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            $formOptions['products'] = $this->getProducts($request);
        }

        $form = $this->createQuickAddForm($formOptions);
        if ($request->isMethod(Request::METHOD_POST)) {
            $form->submit($request);
            if ($processor && $processor->isAllowed()) {
                if ($form->isValid()) {
                    $products = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getData();
                    $products = is_array($products) ? $products : [];
                    $response = $processor->process(
                        [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products],
                        $request
                    );
                    if (!$response) {
                        // reset form
                        $form = $this->createQuickAddForm($formOptions);
                    }
                }
            } else {
                /** @var Session $session */
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans('orob2b.product.frontend.component_not_accessible.message')
                );
            }
        }

        return ['form' => $form, 'response' => $response];
    }

    /**
     * @param array $options
     * @return FormInterface
     */
    protected function createQuickAddForm(array $options = [])
    {
        return $this->formFactory->create(QuickAddType::NAME, null, $options);
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

    /**
     * @param Request $request
     * @return Product[]
     */
    protected function getProducts(Request $request)
    {
        $products = [];

        $data = $request->request->get(QuickAddType::NAME);
        if (!isset($data[QuickAddType::PRODUCTS_FIELD_NAME])) {
            return $products;
        }

        $skus = [];
        foreach ($data[QuickAddType::PRODUCTS_FIELD_NAME] as $productData) {
            $sku = trim($productData[ProductDataStorage::PRODUCT_SKU_KEY]);
            if (strlen($sku) > 0) {
                $skus[] = $sku;
            }
        }

        if (!$skus) {
            return $products;
        }

        $products = $this->getRepository()->getProductWithNamesBySku($skus);
        $productsBySku = [];
        foreach ($products as $product) {
            $productsBySku[strtoupper($product->getSku())] = $product;
        }

        return $productsBySku;
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass($this->productClass)->getRepository($this->productClass);
    }
}

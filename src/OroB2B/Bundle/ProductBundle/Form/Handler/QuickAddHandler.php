<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\Provider\QuickAddFormProvider;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddHandler
{
    /**
     * @var QuickAddFormProvider
     */
    protected $quickAddFormProvider;

    /**
     * @var ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /** @var  ManagerRegistry */
    protected $registry;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /** @var string */
    protected $productClass;

    /**
     * @param QuickAddFormProvider $quickAddFormProvider
     * @param ComponentProcessorRegistry $componentRegistry
     * @param ManagerRegistry $registry
     * @param UrlGeneratorInterface $router
     * @param TranslatorInterface $translator
     */
    public function __construct(
        QuickAddFormProvider $quickAddFormProvider,
        ComponentProcessorRegistry $componentRegistry,
        ManagerRegistry $registry,
        UrlGeneratorInterface $router,
        TranslatorInterface $translator
    ) {
        $this->quickAddFormProvider = $quickAddFormProvider;
        $this->componentRegistry = $componentRegistry;
        $this->registry = $registry;
        $this->router = $router;
        $this->translator = $translator;
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
     * @param string|null $successDefaultRoute
     * @return array
     */
    public function process(Request $request, $successDefaultRoute = null)
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->submitRequest($request, $successDefaultRoute);
        } else {
            $form = $this->quickAddFormProvider->getForm();
        }

        return [
            'form' => $form,
            'response' => null,
        ];
    }

    /**
     * @param Request $request
     * @param string $successDefaultRoute
     * @return array
     */
    protected function submitRequest(Request $request, $successDefaultRoute)
    {
        $response = null;

        $options = [];
        $options['products'] = $this->getProducts($request);

        $processor = $this->getProcessor($this->getComponentName($request));
        if ($processor) {
            $options['validation_required'] = $processor->isValidationRequired();
        }

        $form = $this->quickAddFormProvider->getForm($options);
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
                    $response = new RedirectResponse($this->router->generate($successDefaultRoute));
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

        return [
            'form' => $form,
            'response' => $response,
        ];
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

<?php

namespace Oro\Bundle\ShippingBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Form\Extension\ProductFormExtension;
use Oro\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsType;
use Oro\Bundle\ShippingBundle\Provider\FreightClassesProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles /freight-classes request
 * returns formatted units choices
 */
class AjaxProductShippingOptionsController extends AbstractController
{
    /** Additional options that must be provided to the form that processed within ajax requests */
    private array $ajaxFormsAdditionalOptions = [];

    public function addAjaxFormsAdditionalOption(
        string $ajaxFormsAdditionalOption,
        mixed $ajaxFormsAdditionalOptionValue
    ): void {
        $this->ajaxFormsAdditionalOptions[$ajaxFormsAdditionalOption] = $ajaxFormsAdditionalOptionValue;
    }

    /**
     * Get available FreightClasses codes
     *
     * @Route("/freight-classes", name="oro_shipping_freight_classes", methods={"POST"})
     * @AclAncestor("oro_product_update")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getAvailableProductUnitFreightClassesAction(Request $request)
    {
        $unitCode = $request->request->get('activeUnitCode');

        $productData = $request->request->get(ProductType::NAME);
        if (!is_array($productData)) {
            throw $this->createNotFoundException();
        }
        $product = $this->buildProduct($productData);
        $activeShippingOptions = $this->buildActiveShippingOptions($productData, $unitCode);
        if (!$activeShippingOptions) {
            throw $this->createNotFoundException();
        }
        $activeShippingOptions->setProduct($product);

        $provider = $this->get(FreightClassesProvider::class);

        $formatter = $this->get(UnitLabelFormatter::class);

        $units = $provider->getFreightClasses($activeShippingOptions);

        return new JsonResponse(
            [
                'units' => $formatter->formatChoices($units, (bool)$request->get('short', false)),
            ]
        );
    }

    /**
     * @param array $productData
     * @return Product
     */
    private function buildProduct(array $productData)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, $this->ajaxFormsAdditionalOptions);
        $form->submit($productData);

        return $product;
    }

    /**
     * @param array $productData
     * @param string $unitCode
     * @return ProductShippingOptions|null
     */
    private function buildActiveShippingOptions(array $productData, $unitCode)
    {
        $shippingOptionsData = [];
        if (array_key_exists(ProductFormExtension::FORM_ELEMENT_NAME, $productData) &&
            is_array($productData[ProductFormExtension::FORM_ELEMENT_NAME])
        ) {
            $shippingOptionsData = $productData[ProductFormExtension::FORM_ELEMENT_NAME];
        }
        $activeShippingOptions = null;
        foreach ($shippingOptionsData as $shippingOptionsRow) {
            $shippingOptions = new ProductShippingOptions();
            $form = $this->createForm(
                ProductShippingOptionsType::class,
                $shippingOptions,
                \array_merge(
                    ['by_reference' => true],
                    $this->ajaxFormsAdditionalOptions
                )
            );
            $form->submit($shippingOptionsRow);
            $productUnit = $shippingOptions->getProductUnit();
            if ($productUnit && $unitCode === $productUnit->getCode()) {
                $activeShippingOptions = $shippingOptions;
                break;
            }
        }

        return $activeShippingOptions;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                FreightClassesProvider::class,
                UnitLabelFormatter::class,
            ]
        );
    }
}

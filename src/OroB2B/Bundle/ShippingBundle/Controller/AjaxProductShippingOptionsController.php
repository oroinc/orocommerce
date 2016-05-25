<?php

namespace OroB2B\Bundle\ShippingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Form\Extension\ProductFormExtension;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsType;
use OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProvider;

class AjaxProductShippingOptionsController extends Controller
{
    /**
     * Get available FreightClasses codes
     *
     * @Route("/freight-classes", name="orob2b_shipping_freight_classes")
     * @Method({"POST"})
     * @AclAncestor("orob2b_product_update")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getAvailableProductUnitFreightClasses(Request $request)
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

        /* @var $provider FreightClassesProvider */
        $provider = $this->get('orob2b_shipping.provider.measure_units.freight');

        /* @var $formatter UnitLabelFormatter */
        $formatter = $this->get('orob2b_shipping.formatter.freight_class_label');

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
        $form = $this->createForm(ProductType::NAME, $product);
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
            $form = $this->createForm(ProductShippingOptionsType::NAME, $shippingOptions, ['by_reference' => true]);
            $form->submit($shippingOptionsRow);
            $productUnit = $shippingOptions->getProductUnit();
            if ($productUnit && $unitCode === $productUnit->getCode()) {
                $activeShippingOptions = $shippingOptions;
                break;
            }
        }

        return $activeShippingOptions;
    }
}

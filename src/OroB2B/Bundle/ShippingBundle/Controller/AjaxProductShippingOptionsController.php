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
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Form\Extension\ProductFormExtension;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsType;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

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

        /* @var $provider AbstractMeasureUnitProvider */
        $provider = $this->get('orob2b_shipping.provider.measure_units.freight');

        $codes = $provider->getUnitsCodes();
        $codes = $provider->formatUnitsCodes(array_combine($codes, $codes), (bool)$request->get('short', false));

        return new JsonResponse(
            [
                'units' => $codes,
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

        $shippingOptions = new ProductShippingOptions();
        $form = $this->createForm(ProductShippingOptionsType::NAME, $shippingOptions);
        $activeShippingOptions = null;
        foreach ($shippingOptionsData as $shippingOptionsRow) {
            $form->submit($shippingOptionsRow, true);
            $productUnit = $shippingOptions->getProductUnit();
            if ($productUnit && $unitCode === $productUnit->getCode()) {
                $activeShippingOptions = $shippingOptions;
                break;
            }
        }

        return $activeShippingOptions;
    }
}

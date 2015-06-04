<?php

namespace OroB2B\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;

class AjaxProductUnitController extends Controller
{
    /**
     * @Route("/product-units/{id}", name="orob2b_product_unit_product_units", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_product_view")
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function getProductUnitsAction(Product $product)
    {
        $units = $this->getRepository()->getProductUnits($product);
        $result = [];
        $translator = $this->getTranslator();

        foreach ($units as $unit) {
            $result[$unit->getCode()] = $translator->trans(
                sprintf('orob2b.product_unit.%s.label.full', $unit->getCode())
            );
        }

        return new JsonResponse(['units' => $result]);
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        return $this->get('doctrine')->getRepository(
            $this->container->getParameter('orob2b_product.product_unit.class')
        );
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }
}

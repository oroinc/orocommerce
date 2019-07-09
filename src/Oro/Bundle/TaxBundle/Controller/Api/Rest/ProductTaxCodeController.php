<?php

namespace Oro\Bundle\TaxBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("product_tax_code")
 * @NamePrefix("oro_api_")
 */
class ProductTaxCodeController extends FOSRestController
{
    /**
     * @Patch("taxcode/product/{id}/patch")
     * @AclAncestor("oro_product_update")
     *
     * @param Request $request
     * @param Product $product
     *
     * @return Response
     */
    public function patchAction(Request $request, Product $product)
    {
        $taxCodeId = $request->get('taxCode');
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');

        /** @var ProductTaxCodeRepository $taxCodeRepository */
        $taxCodeRepository = $doctrineHelper->getEntityRepositoryForClass(ProductTaxCode::class);

        $newTaxCode = $taxCodeId ? $taxCodeRepository->find($taxCodeId) : null;
        $manager = $doctrineHelper->getEntityManagerForClass(Product::class);

        $product->setTaxCode($newTaxCode);
        $manager->flush($product);

        return parent::handleView($this->view([], Response::HTTP_OK));
    }
}

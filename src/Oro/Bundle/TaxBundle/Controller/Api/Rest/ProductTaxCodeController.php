<?php

namespace Oro\Bundle\TaxBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManager;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;

use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

/**
 * @RouteResource("product_tax_code")
 * @NamePrefix("oro_api_")
 */
class ProductTaxCodeController extends FOSRestController
{
    /**
     * @Patch("taxcode/product/{id}/patch")
     *
     * @param Request $request
     * @param Product $product
     *
     * @return Response
     */
    public function patchAction(Request $request, Product $product)
    {
        $taxCode = $request->get('taxCode');
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');

        /** @var ProductTaxCodeRepository $taxCodeRepository */
        $taxCodeRepository = $doctrineHelper->getEntityRepositoryForClass(ProductTaxCode::class);

        $oldTaxCode = $taxCodeRepository->findOneByProduct($product);
        $newTaxCode = $taxCode ? $taxCodeRepository->findOneBy(['code' => $taxCode]) : null;
        $manager = $doctrineHelper->getEntityManagerForClass(ProductTaxCode::class);

        $manager->transactional(function (EntityManager $manager) use ($oldTaxCode, $newTaxCode, $product) {
            // Added two flushes because doctrine runs insert query before delete query.
            // ProductTaxCode have unique constraint for product relation.
            if ($oldTaxCode) {
                $oldTaxCode->removeProduct($product);
                $manager->flush($oldTaxCode);
            }

            if ($newTaxCode) {
                $newTaxCode->addProduct($product);
                $manager->flush($newTaxCode);
            }
        });

        return parent::handleView($this->view([], Codes::HTTP_OK));
    }
}

<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Get;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @NamePrefix("orob2b_api_")
 */
class ProductController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete product",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_product_delete",
     *      type="entity",
     *      class="OroB2BProductBundle:Product",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get available units for the product
     *
     * @Get("/products/{id}/units-available", requirements={
     *     "id": "\d+"
     * }))
     *
     * @ApiDoc(
     *      description="Get available units for the product",
     *      resource=true
     * )
     *
     * @Acl(
     *      id="orob2b_product_view",
     *      type="entity",
     *      class="OroB2BProductBundle:ProductUnit",
     *      permission="VIEW"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function getAvailableUnitsAction($id)
    {
        $em = $this->getDoctrine()->getManagerForClass('OroB2BProductBundle:Product');
        /** @var $product Product */
        $product = $em->getRepository('OroB2BProductBundle:Product')->find($id);
        if (!$product) {
            return $this->handleView(
                $this->view(['successful' => false], Codes::HTTP_NOT_FOUND)
            );
        }
        $data = [];
        $codes = $product->getAvailableUnitCodes();
        /* @var $translator Translator */
        $translator = $this->get('translator');
        foreach ($codes as $code) {
            $label = 'orob2b.product_unit.' . $code . '.label.full';
            $data[$code] = $translator->trans($label);
        }

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'data' => $data
                ],
                Codes::HTTP_OK
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_product.product.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}

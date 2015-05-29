<?php

namespace OroB2B\Bundle\PaymentBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("paymentterm")
 * @NamePrefix("orob2b_api_")
 */
class PaymentTermController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete payment term",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_payment_term_delete",
     *      type="entity",
     *      class="OroB2BPaymentBundle:PaymentTerm",
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
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_payment.payment_term.manager.api');
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

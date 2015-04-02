<?php

namespace OroB2B\Bundle\RFPBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @NamePrefix("orob2b_rfp_request_status_api_")
 */
class RequestStatusController extends RestController
{
    /**
     * Rest delete
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete RequestStatus",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_rfp_request_status_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroB2BRFPBundle:RequestStatus"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->get('doctrine')->getManager();
        $requesStatus = $em->getRepository('OroB2BRFPBundle:RequestStatus')->find($id);

        if (null === $requesStatus) {
            return new JsonResponse(
                $this->get('translator')->trans('Item not found'),
                Codes::HTTP_NOT_FOUND
            );
        }

        $requesStatus->setDeleted(true);
        $em->flush();

        $result = new Response(null, Codes::HTTP_NO_CONTENT);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        throw new \LogicException('This method should not be called');
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

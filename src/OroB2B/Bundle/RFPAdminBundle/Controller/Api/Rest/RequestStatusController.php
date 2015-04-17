<?php

namespace OroB2B\Bundle\RFPAdminBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @NamePrefix("orob2b_api_rfp_")
 */
class RequestStatusController extends FOSRestController implements ClassResourceInterface
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
     *      id="orob2b_rfp_admin_request_status_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroB2BRFPAdminBundle:RequestStatus"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        $em = $this->get('doctrine')->getManagerForClass('OroB2BRFPAdminBundle:RequestStatus');
        $requestStatus = $em->getRepository('OroB2BRFPAdminBundle:RequestStatus')->find($id);

        if (null === $requestStatus) {
            return $this->handleView(
                $this->view(['successful' => false], Codes::HTTP_NOT_FOUND)
            );
        }

        $defaultRequestStatusName = $this->get('oro_config.manager')->get('oro_b2b_rfp_admin.default_request_status');

        if ($defaultRequestStatusName === $requestStatus->getName()) {
            return $this->handleView(
                $this->view(['successful' => false], Codes::HTTP_FORBIDDEN)
            );
        }

        $requestStatus->setDeleted(true);
        $em->flush();

        $result = new Response(null, Codes::HTTP_NO_CONTENT);

        return $result;
    }

    /**
     * @param $id
     *
     * @ApiDoc(
     *      description="Restore RequestStatus",
     *      resource=true
     * )
     * @AclAncestor("orob2b_rfp_admin_request_status_delete")
     *
     * @return Response
     */
    public function restoreAction($id)
    {
        $em = $this->get('doctrine')->getManagerForClass('OroB2BRFPAdminBundle:RequestStatus');
        $requestStatus = $em->getRepository('OroB2BRFPAdminBundle:RequestStatus')->find($id);

        if (null === $requestStatus) {
            return new JsonResponse(
                $this->get('translator')->trans('orob2b.rfp.message.request_status_not_found'),
                Codes::HTTP_NOT_FOUND
            );
        }

        $requestStatus->setDeleted(false);
        $em->flush();

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $this->get('translator')->trans('orob2b.rfp.message.request_status_restored')
                ],
                Codes::HTTP_OK
            )
        );
    }
}

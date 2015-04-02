<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestStatusController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_rfp_request_status_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_request_status_view",
     *      type="entity",
     *      class="OroB2BRFPBundle:RequestStatus",
     *      permission="VIEW"
     * )
     *
     * @param RequestStatus $requestStatus
     * @return array
     */
    public function viewAction(RequestStatus $requestStatus)
    {
        return [
            'entity' => $requestStatus
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_rfp_request_status_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_rfp_request_status_view")
     *
     * @param RequestStatus $requestStatus
     * @return array
     */
    public function infoAction(RequestStatus $requestStatus)
    {
        return [
            'entity' => $requestStatus
        ];
    }

    /**
     * @Route("/", name="orob2b_rfp_request_status_index")
     * @Template
     * @AclAncestor("orob2b_rfp_request_status_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_rfp.request.status.class')
        ];
    }
}

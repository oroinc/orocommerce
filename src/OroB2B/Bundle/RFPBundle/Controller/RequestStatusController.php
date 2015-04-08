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

    /**
     * @Route("/create", name="orob2b_rfp_request_status_create")
     * @Template("@OroB2BRFP/RequestStatus/update.html.twig")
     * @Acl(
     *     id="orob2b_rfp_request_status_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroB2BRFPBundle:RequestStatus"
     * )
     */
    public function createAction()
    {
        $requestStatus = new RequestStatus();
        return $this->process($requestStatus);
    }

    /**
     * @Route("/update/{id}", name="orob2b_rfp_request_status_update", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_rfp_request_status_create")
     */
    public function updateAction(RequestStatus $requestStatus)
    {
        return $this->process($requestStatus);
    }

    protected function process(RequestStatus $requestStatus)
    {
        return $this->get('oro_form.model.update_handler')
            ->handleUpdate(
                $requestStatus,
                $this->get('orob2b_rfp.form.request_status'),
                function (RequestStatus $requestStatus) {
                    return [
                        'route' => 'orob2b_rfp_request_status_update',
                        'parameters' => [
                            'id' => $requestStatus->getId()
                        ]
                    ];
                },
                function (RequestStatus $requestStatus) {
                    return [
                        'route' => 'orob2b_rfp_request_status_view',
                        'parameters' => [
                            'id' => $requestStatus->getId()
                        ]
                    ];
                },
                $this->get('translator')->trans('orob2b.rfp.message.request_status_saved'),
                $this->get('orob2b_rfp.form.handler.request_status')
            )
        ;
    }
}

<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Form\Handler\RequestStatusHandler;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestStatusType;

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
     * @Template("OroB2BRFPBundle:RequestStatus:view.html.twig")
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
            'entity_class' => $this->container->getParameter('orob2b_rfp.entity.request.status.class')
        ];
    }

    /**
     * @Route("/create", name="orob2b_rfp_request_status_create")
     * @Template("OroB2BRFPBundle:RequestStatus:update.html.twig")
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
     * @Acl(
     *     id="orob2b_rfp_request_status_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroB2BRFPBundle:RequestStatus"
     * )
     *
     * @param RequestStatus $requestStatus
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(RequestStatus $requestStatus)
    {
        return $this->process($requestStatus);
    }

    /**
     * @param RequestStatus $requestStatus
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function process(RequestStatus $requestStatus)
    {
        $form = $this->createForm(RequestStatusType::NAME);

        $handler = new RequestStatusHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('orob2b_rfp.entity.request.status.class')
            )
        );
        $handler->setDefaultLocale($this->container->getParameter('stof_doctrine_extensions.default_locale'));

        return $this->get('oro_form.model.update_handler')
            ->handleUpdate(
                $requestStatus,
                $form,
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
                $handler
            )
            ;
    }
}

<?php

namespace OroB2B\Bundle\RFPAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Form\Handler\RequestChangeStatusHandler;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestChangeStatusType;

class RequestController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_rfp_admin_request_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_admin_request_view",
     *      type="entity",
     *      class="OroB2BRFPAdminBundle:Request",
     *      permission="VIEW"
     * )
     *
     * @param Request $request
     * @return array
     */
    public function viewAction(Request $request)
    {
        return [
            'entity' => $request
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_rfp_admin_request_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_rfp_admin_request_view")
     *
     * @param Request $request
     * @return array
     */
    public function infoAction(Request $request)
    {
        return [
            'entity' => $request
        ];
    }

    /**
     * @Route("/", name="orob2b_rfp_admin_request_index")
     * @Template
     * @AclAncestor("orob2b_rfp_admin_request_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_rfp_admin.request.class')
        ];
    }

    /**
     * @Route("/change_status/{id}", name="orob2b_rfp_admin_request_change_status", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_admin_request_update",
     *      type="entity",
     *      class="OroB2BRFPAdminBundle:Request",
     *      permission="EDIT"
     * )
     *
     * @param Request $request
     * @return array
     */
    public function changeStatusAction(Request $request)
    {
        if (!$this->getRequest()->get('_widgetContainer')) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(RequestChangeStatusType::NAME, ['status' => $request->getStatus()]);
        $handler = new RequestChangeStatusHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BRFPAdminBundle:Request'),
            $this->container->get('templating')
        );

        $formAction = $this->get('router')->generate('orob2b_rfp_admin_request_change_status', ['id' => $request->getId()]);

        return [
            'entity'     => $request,
            'saved'      => $handler->process($request) ? true : false,
            'form'       => $form->createView(),
            'formAction' => $formAction
        ];
    }
}

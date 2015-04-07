<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Form\Handler\RequestHandler;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestType;

class RequestController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_rfp_request_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_request_view",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
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
     * @Route("/info/{id}", name="orob2b_rfp_request_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_rfp_request_view")
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
     * @Route("/", name="orob2b_rfp_request_index")
     * @Template
     * @AclAncestor("orob2b_rfp_request_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_rfp.request.class')
        ];
    }

    /**
     * @Route("/update/{id}", name="orob2b_rfp_request_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_rfp_request_update",
     *      type="entity",
     *      class="OroB2BRFPBundle:Request",
     *      permission="EDIT"
     * )
     *
     * @param Request $request
     * @return array
     */
    public function updateAction(Request $request)
    {
        $form = $this->createForm(RequestType::NAME, ['status' => $request->getStatus()]);

        $handler = new RequestHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BRFPBundle:Request'),
            $this->container->get('templating')
        );

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $request,
            $form,
            function (Request $request) {
                return ['route' => 'orob2b_rfp_request_update', 'parameters' => ['id' => $request->getId()]];
            },
            function (Request $request) {
                return ['route' => 'orob2b_rfp_request_view', 'parameters' => ['id' => $request->getId()]];
            },
            $this->get('translator')->trans('orob2b.rfp.controller.request.saved.message'),
            $handler
        );
    }
}

<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Form\Type\RequestType;

class RequestController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_rfp_request_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_rfp_request_view",
     *      type="entity",
     *      class="OroRFPBundle:Request",
     *      permission="VIEW"
     * )
     *
     * @param RFPRequest $rfpRequest
     * @return array
     */
    public function viewAction(RFPRequest $rfpRequest)
    {
        return [
            'entity' => $rfpRequest,
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_rfp_request_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_rfp_request_view")
     *
     * @param RFPRequest $rfpRequest
     * @return array
     */
    public function infoAction(RFPRequest $rfpRequest)
    {
        return [
            'entity' => $rfpRequest,
        ];
    }

    /**
     * @Route("/", name="oro_rfp_request_index")
     * @Template
     * @AclAncestor("oro_rfp_request_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_rfp.entity.request.class'),
        ];
    }

    /**
     * @Route("/update/{id}", name="oro_rfp_request_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="oro_rfp_request_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroRFPBundle:Request"
     * )
     *
     * @param RFPRequest $rfpRequest
     *
     * @return array|RedirectResponse
     */
    public function updateAction(RFPRequest $rfpRequest)
    {
        return $this->update($rfpRequest);
    }

    /**
     * @param RFPRequest $rfpRequest
     * @return array|RedirectResponse
     */
    protected function update(RFPRequest $rfpRequest)
    {
        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');

        return $handler->handleUpdate(
            $rfpRequest,
            $this->createForm(RequestType::NAME, $rfpRequest),
            function (RFPRequest $request) {
                return [
                    'route' => 'oro_rfp_request_update',
                    'parameters' => ['id' => $request->getId()],
                ];
            },
            function (RFPRequest $request) {
                return [
                    'route' => 'oro_rfp_request_view',
                    'parameters' => ['id' => $request->getId()],
                ];
            },
            $this->get('translator')->trans('oro.rfp.controller.request.saved.message')
        );
    }
}

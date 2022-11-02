<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Form\Type\RequestType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Back-office CRUD for RFQs.
 */
class RequestController extends AbstractController
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
     */
    public function viewAction(RFPRequest $rfpRequest): array
    {
        return [
            'entity' => $rfpRequest,
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_rfp_request_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_rfp_request_view")
     */
    public function infoAction(RFPRequest $rfpRequest): array
    {
        return [
            'entity' => $rfpRequest,
        ];
    }

    /**
     * @Route("/", name="oro_rfp_request_index")
     * @Template
     * @AclAncestor("oro_rfp_request_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => RFPRequest::class,
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
     */
    public function updateAction(RFPRequest $rfpRequest): array|RedirectResponse
    {
        return $this->update($rfpRequest);
    }

    protected function update(RFPRequest $rfpRequest): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $rfpRequest,
            $this->createForm(RequestType::class, $rfpRequest),
            $this->get(TranslatorInterface::class)->trans('oro.rfp.controller.request.saved.message')
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}

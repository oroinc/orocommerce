<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Form\Type\RequestType;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Back-office CRUD for RFQs.
 */
class RequestController extends AbstractController
{
    #[Route(path: '/view/{id}', name: 'oro_rfp_request_view', requirements: ['id' => '\d+'])]
    #[Template('@OroRFP/Request/view.html.twig')]
    #[Acl(id: 'oro_rfp_request_view', type: 'entity', class: RFPRequest::class, permission: 'VIEW')]
    public function viewAction(RFPRequest $rfpRequest): array
    {
        return [
            'entity' => $rfpRequest,
        ];
    }

    #[Route(path: '/info/{id}', name: 'oro_rfp_request_info', requirements: ['id' => '\d+'])]
    #[Template('@OroRFP/Request/info.html.twig')]
    #[AclAncestor('oro_rfp_request_view')]
    public function infoAction(RFPRequest $rfpRequest): array
    {
        return [
            'entity' => $rfpRequest,
        ];
    }

    #[Route(path: '/', name: 'oro_rfp_request_index')]
    #[Template('@OroRFP/Request/index.html.twig')]
    #[AclAncestor('oro_rfp_request_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => RFPRequest::class,
        ];
    }

    #[Route(path: '/update/{id}', name: 'oro_rfp_request_update', requirements: ['id' => '\d+'])]
    #[Template('@OroRFP/Request/update.html.twig')]
    #[Acl(id: 'oro_rfp_request_update', type: 'entity', class: RFPRequest::class, permission: 'EDIT')]
    public function updateAction(RFPRequest $rfpRequest): array|RedirectResponse
    {
        return $this->update($rfpRequest);
    }

    protected function update(RFPRequest $rfpRequest): array|RedirectResponse
    {
        $form = $this->createForm(
            RequestType::class,
            $rfpRequest,
            [
                'validation_groups' => $this->getValidationGroups($rfpRequest),
            ]
        );
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $rfpRequest,
            $form,
            $this->container->get(TranslatorInterface::class)->trans('oro.rfp.controller.request.saved.message')
        );
    }

    protected function getValidationGroups(RFPRequest $rfpRequest): GroupSequence|array|string
    {
        return new GroupSequence([Constraint::DEFAULT_GROUP, 'request_update']);
    }

    #[\Override]
    public static function getSubscribedServices(): array
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

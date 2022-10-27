<?php

namespace Oro\Bundle\RFPBundle\Form\Handler;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\FormBundle\Model\UpdateInterface;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Mailer\RequestRepresentativesNotifier;
use Symfony\Bundle\FrameworkBundle\Routing\Router as SymfonyRouter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Handles update action for Request entity.
 */
class RequestUpdateHandler extends UpdateHandlerFacade
{
    private RequestRepresentativesNotifier $representativesNotifier;
    private AuthorizationCheckerInterface $authorizationChecker;
    private SymfonyRouter $symfonyRouter;

    public function setRepresentativesNotifier(RequestRepresentativesNotifier $representativesNotifier): void
    {
        $this->representativesNotifier = $representativesNotifier;
    }

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): void
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function setSymfonyRouter(SymfonyRouter $symfonyRouter): void
    {
        $this->symfonyRouter = $symfonyRouter;
    }

    private function createResponse(Request $rfpRequest): RedirectResponse
    {
        if ($this->authorizationChecker->isGranted('VIEW', $rfpRequest)) {
            return new RedirectResponse($this->symfonyRouter->generate(
                'oro_rfp_frontend_request_view',
                ['id' => $rfpRequest->getId()]
            ));
        }

        $this->session->set('last_success_rfq_id', $rfpRequest->getId());

        return new RedirectResponse($this->symfonyRouter->generate('oro_rfp_frontend_request_success', []));
    }

    /**
     * {@inheritDoc}
     */
    protected function constructResponse(
        UpdateInterface $update,
        HttpRequest $request,
        ?string $saveMessage
    ): array|RedirectResponse {
        /** @var Request $entity */
        $entity = $update->getFormData();

        if ($request->get('_wid')) {
            $result = parent::constructResponse($update, $request, $saveMessage);
        } else {
            $this->session->getFlashBag()->add('success', $saveMessage);

            $result = $this->createResponse($entity);
        }

        $this->representativesNotifier->sendConfirmationEmail($entity);
        $this->representativesNotifier->notifyRepresentatives($entity);

        return $result;
    }
}

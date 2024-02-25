<?php

namespace Oro\Bundle\ConsentBundle\Controller;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains CRUD actions for consents
 */
class ConsentController extends AbstractController
{
    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_consent_index')]
    #[Template]
    #[AclAncestor('oro_consent_view')]
    public function indexAction()
    {
        return [
            'entity_class' => Consent::class
        ];
    }

    /**
     * Create consent
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/create', name: 'oro_consent_create')]
    #[Template('@OroConsent/Consent/update.html.twig')]
    #[Acl(id: 'oro_consent_create', type: 'entity', class: Consent::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        $createMessage = $this->container->get(TranslatorInterface::class)->trans('oro.consent.form.messages.created');

        return $this->update(new Consent(), $request, $createMessage);
    }

    /**
     * Edit consent form
     *
     *
     * @param Consent $consent
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_consent_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_consent_update', type: 'entity', class: Consent::class, permission: 'EDIT')]
    public function updateAction(Consent $consent, Request $request)
    {
        $updateMessage = $this->container->get(TranslatorInterface::class)->trans('oro.consent.form.messages.saved');

        return $this->update($consent, $request, $updateMessage);
    }

    /**
     * @param Consent $consent
     * @param Request $request
     * @param string $message
     *
     * @return array|RedirectResponse
     */
    protected function update(Consent $consent, Request $request, $message = '')
    {
        $updateHandler = $this->container->get(UpdateHandlerFacade::class);

        return $updateHandler->update(
            $consent,
            $this->createForm(ConsentType::class, $consent),
            $message,
            $request,
            null
        );
    }

    /**
     * @param Consent $consent
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_consent_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_consent_view', type: 'entity', class: Consent::class, permission: 'VIEW')]
    public function viewAction(Consent $consent)
    {
        return [
            'entity' => $consent,
        ];
    }

    /**
     * @param Consent $consent
     * @return array
     */
    #[Route(path: '/info/{id}', name: 'oro_consent_info', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_consent_view')]
    public function infoAction(Consent $consent)
    {
        return [
            'consent' => $consent,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class,
            ]
        );
    }
}

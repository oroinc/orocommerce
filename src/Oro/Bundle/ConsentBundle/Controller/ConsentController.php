<?php

namespace Oro\Bundle\ConsentBundle\Controller;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains CRUD actions for consents
 */
class ConsentController extends Controller
{
    /**
     * @Route("/", name="oro_consent_index")
     * @Template
     * @AclAncestor("oro_consent_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Consent::class
        ];
    }

    /**
     * Create consent
     *
     * @Route("/create", name="oro_consent_create")
     * @Template("OroConsentBundle:Consent:update.html.twig")
     * @Acl(
     *      id="oro_consent_create",
     *      type="entity",
     *      class="OroConsentBundle:Consent",
     *      permission="CREATE"
     * )
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $createMessage = $this->get('translator')->trans('oro.consent.form.messages.created');

        return $this->update(new Consent(), $request, $createMessage);
    }

    /**
     * Edit consent form
     *
     * @Route("/update/{id}", name="oro_consent_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_consent_update",
     *      type="entity",
     *      class="OroConsentBundle:Consent",
     *      permission="EDIT"
     * )
     *
     * @param Consent $consent
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Consent $consent, Request $request)
    {
        $updateMessage = $this->get('translator')->trans('oro.consent.form.messages.saved');

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
        /** @var UpdateHandlerFacade $updateHandler */
        $updateHandler = $this->get('oro_form.update_handler');

        return $updateHandler->update(
            $consent,
            $this->createForm(ConsentType::class, $consent),
            $message,
            $request,
            null
        );
    }

    /**
     * @Route("/view/{id}", name="oro_consent_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_consent_view",
     *      type="entity",
     *      class="OroConsentBundle:Consent",
     *      permission="VIEW"
     * )
     *
     * @param Consent $consent
     * @return array
     */
    public function viewAction(Consent $consent)
    {
        return [
            'entity' => $consent,
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_consent_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_consent_view")
     *
     * @param Consent $consent
     *
     * @return array
     */
    public function infoAction(Consent $consent)
    {
        return [
            'consent' => $consent,
        ];
    }
}

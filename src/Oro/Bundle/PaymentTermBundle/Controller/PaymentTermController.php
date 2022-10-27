<?php

namespace Oro\Bundle\PaymentTermBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for payment terms.
 */
class PaymentTermController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_payment_term_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_payment_term_view",
     *      type="entity",
     *      class="OroPaymentTermBundle:PaymentTerm",
     *      permission="VIEW"
     * )
     */
    public function viewAction(PaymentTerm $paymentTerm): array
    {
        return [
            'entity' => $paymentTerm
        ];
    }

    /**
     * @Route("/", name="oro_payment_term_index")
     * @Template
     * @AclAncestor("oro_payment_term_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => PaymentTerm::class
        ];
    }

    /**
     * Create payment term form
     *
     * @Route("/create", name="oro_payment_term_create")
     * @Template("@OroPaymentTerm/PaymentTerm/update.html.twig")
     * @Acl(
     *      id="oro_payment_term_create",
     *      type="entity",
     *      class="OroPaymentTermBundle:PaymentTerm",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new PaymentTerm());
    }

    /**
     * Edit payment term form
     *
     * @Route("/update/{id}", name="oro_payment_term_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_payment_term_update",
     *      type="entity",
     *      class="OroPaymentTermBundle:PaymentTerm",
     *      permission="EDIT"
     * )
     */
    public function updateAction(PaymentTerm $paymentTerm): array|RedirectResponse
    {
        return $this->update($paymentTerm);
    }

    /**
     * @Route("/widget/info/{id}", name="oro_payment_term_widget_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_payment_term_view")
     */
    public function infoAction(PaymentTerm $entity): array
    {
        return [
            'entity' => $entity,
        ];
    }

    protected function update(PaymentTerm $paymentTerm): array|RedirectResponse
    {
        $form = $this->createForm(PaymentTermType::class, $paymentTerm);

        return $this->get(UpdateHandlerFacade::class)->update(
            $paymentTerm,
            $form,
            $this->get(TranslatorInterface::class)->trans('oro.paymentterm.controller.paymentterm.saved.message')
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

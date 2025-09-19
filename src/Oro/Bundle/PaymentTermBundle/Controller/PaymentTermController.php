<?php

namespace Oro\Bundle\PaymentTermBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermType;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for payment terms.
 */
class PaymentTermController extends AbstractController
{
    #[Route(path: '/view/{id}', name: 'oro_payment_term_view', requirements: ['id' => '\d+'])]
    #[Template('@OroPaymentTerm/PaymentTerm/view.html.twig')]
    #[Acl(id: 'oro_payment_term_view', type: 'entity', class: PaymentTerm::class, permission: 'VIEW')]
    public function viewAction(PaymentTerm $paymentTerm): array
    {
        return [
            'entity' => $paymentTerm
        ];
    }

    #[Route(path: '/', name: 'oro_payment_term_index')]
    #[Template('@OroPaymentTerm/PaymentTerm/index.html.twig')]
    #[AclAncestor('oro_payment_term_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => PaymentTerm::class
        ];
    }

    /**
     * Create payment term form
     */
    #[Route(path: '/create', name: 'oro_payment_term_create')]
    #[Template('@OroPaymentTerm/PaymentTerm/update.html.twig')]
    #[Acl(id: 'oro_payment_term_create', type: 'entity', class: PaymentTerm::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new PaymentTerm());
    }

    /**
     * Edit payment term form
     */
    #[Route(path: '/update/{id}', name: 'oro_payment_term_update', requirements: ['id' => '\d+'])]
    #[Template('@OroPaymentTerm/PaymentTerm/update.html.twig')]
    #[Acl(id: 'oro_payment_term_update', type: 'entity', class: PaymentTerm::class, permission: 'EDIT')]
    public function updateAction(PaymentTerm $paymentTerm): array|RedirectResponse
    {
        return $this->update($paymentTerm);
    }

    #[Route(path: '/widget/info/{id}', name: 'oro_payment_term_widget_info', requirements: ['id' => '\d+'])]
    #[Template('@OroPaymentTerm/PaymentTerm/info.html.twig')]
    #[AclAncestor('oro_payment_term_view')]
    public function infoAction(PaymentTerm $entity): array
    {
        return [
            'entity' => $entity,
        ];
    }

    protected function update(PaymentTerm $paymentTerm): array|RedirectResponse
    {
        $form = $this->createForm(PaymentTermType::class, $paymentTerm);

        return $this->container->get(UpdateHandlerFacade::class)->update(
            $paymentTerm,
            $form,
            $this->container->get(TranslatorInterface::class)
                ->trans('oro.paymentterm.controller.paymentterm.saved.message')
        );
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

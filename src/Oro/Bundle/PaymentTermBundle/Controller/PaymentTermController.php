<?php

namespace Oro\Bundle\PaymentTermBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermType;

class PaymentTermController extends Controller
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
     *
     * @param PaymentTerm $paymentTerm
     * @return array
     */
    public function viewAction(PaymentTerm $paymentTerm)
    {
        return [
            'entity' => $paymentTerm
        ];
    }

    /**
     * @Route("/", name="oro_payment_term_index")
     * @Template
     * @AclAncestor("oro_payment_term_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_payment_term.entity.payment_term.class')
        ];
    }

    /**
     * Create payment term form
     *
     * @Route("/create", name="oro_payment_term_create")
     * @Template("OroPaymentTermBundle:PaymentTerm:update.html.twig")
     * @Acl(
     *      id="oro_payment_term_create",
     *      type="entity",
     *      class="OroPaymentTermBundle:PaymentTerm",
     *      permission="CREATE"
     * )
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new PaymentTerm(), $request);
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
     * @param PaymentTerm $paymentTerm
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(PaymentTerm $paymentTerm, Request $request)
    {
        return $this->update($paymentTerm, $request);
    }

    /**
     * @Route("/widget/info/{id}", name="oro_payment_term_widget_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_payment_term_view")
     * @param PaymentTerm $entity
     * @return array
     */
    public function infoAction(PaymentTerm $entity)
    {
        return [
            'entity' => $entity,
        ];
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(PaymentTerm $paymentTerm, Request $request)
    {
        $form = $this->createForm(PaymentTermType::NAME, $paymentTerm);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $paymentTerm,
            $form,
            function (PaymentTerm $paymentTerm) {
                return [
                    'route' => 'oro_payment_term_update',
                    'parameters' => ['id' => $paymentTerm->getId()]
                ];
            },
            function (PaymentTerm $paymentTerm) {
                return [
                    'route' => 'oro_payment_term_view',
                    'parameters' => ['id' => $paymentTerm->getId()]
                ];
            },
            $this->get('translator')->trans('oro.paymentterm.controller.paymentterm.saved.message')
        );
    }
}

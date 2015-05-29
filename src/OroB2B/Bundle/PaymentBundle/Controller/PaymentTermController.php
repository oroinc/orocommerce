<?php

namespace OroB2B\Bundle\PaymentBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermType;

class PaymentTermController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_payment_term_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_payment_term_view",
     *      type="entity",
     *      class="OroB2BPaymentBundle:PaymentTerm",
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
     * @Route("/", name="orob2b_payment_term_index")
     * @Template
     * @AclAncestor("orob2b_payment_term_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_payment.entity.payment_term.class')
        ];
    }

    /**
     * Create payment term form
     *
     * @Route("/create", name="orob2b_payment_term_create")
     * @Template("OroB2BPaymentBundle:PaymentTerm:update.html.twig")
     * @Acl(
     *      id="orob2b_payment_term_create",
     *      type="entity",
     *      class="OroB2BPaymentBundle:PaymentTerm",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new PaymentTerm());
    }

    /**
     * Edit payment term form
     *
     * @Route("/update/{id}", name="orob2b_payment_term_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_payment_term_update",
     *      type="entity",
     *      class="OroB2BPaymentBundle:PaymentTerm",
     *      permission="EDIT"
     * )
     * @param PaymentTerm $paymentTerm
     * @return array|RedirectResponse
     */
    public function updateAction(PaymentTerm $paymentTerm)
    {
        return $this->update($paymentTerm);
    }

    /**
     * @Route("/widget/info/{id}", name="orob2b_payment_term_widget_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_payment_term_view")
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
     * @return array|RedirectResponse
     */
    protected function update(PaymentTerm $paymentTerm)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $paymentTerm,
            $this->createForm(PaymentTermType::NAME, $paymentTerm),
            function (PaymentTerm $paymentTerm) {
                return [
                    'route' => 'orob2b_payment_term_update',
                    'parameters' => ['id' => $paymentTerm->getId()]
                ];
            },
            function (PaymentTerm $paymentTerm) {
                return [
                    'route' => 'orob2b_payment_term_view',
                    'parameters' => ['id' => $paymentTerm->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.payment.controller.paymentterm.saved.message')
        );
    }
}

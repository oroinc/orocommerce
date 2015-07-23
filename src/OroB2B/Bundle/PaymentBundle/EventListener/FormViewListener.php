<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository;

class FormViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(TranslatorInterface $translator, DoctrineHelper $doctrineHelper)
    {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCustomerView(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $customerId = $this->request->get('id');
        /** @var Customer $customer */
        $customer = $this->doctrineHelper->getEntityReference('OroB2BCustomerBundle:Customer', $customerId);

        $paymentTerm = $this->getPaymentRepository()->getOnePaymentTermByCustomer($customer);
        if ($paymentTerm) {
            $template = $event->getEnvironment()->render(
                'OroB2BPaymentBundle:Customer:payment_term_view.html.twig',
                ['paymentTerm' => $paymentTerm]
            );
        } else {
            $customerGroupPaymentTerm = null;
            if ($customer->getGroup()) {
                $customerGroupPaymentTerm =
                    $this->getPaymentRepository()->getOnePaymentTermByCustomerGroup($customer->getGroup());
            }

            $template = $event->getEnvironment()->render(
                'OroB2BPaymentBundle:Customer:payment_term_for_customer_view.html.twig',
                ['customerGroupPaymentTerm' => $customerGroupPaymentTerm]
            );
        }
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCustomerGroupView(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $groupId = $this->request->get('id');
        /** @var CustomerGroup $group */
        $group = $this->doctrineHelper->getEntityReference('OroB2BCustomerBundle:CustomerGroup', $groupId);
        $paymentTerm = $this->getPaymentRepository()->getOnePaymentTermByCustomerGroup($group);

        $template = $event->getEnvironment()->render(
            'OroB2BPaymentBundle:Customer:payment_term_view.html.twig',
            ['paymentTerm' => $paymentTerm]);
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPaymentBundle:Customer:payment_term_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }


    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return PaymentTermRepository
     */
    private function getPaymentRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroB2BPaymentBundle:PaymentTerm');
    }
}

<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
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
    public function onAccountView(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $accountId = $this->request->get('id');
        /** @var Account $account */
        $account = $this->doctrineHelper->getEntityReference('OroB2BAccountBundle:Account', $accountId);

        $paymentTerm = $this->getPaymentRepository()->getOnePaymentTermByAccount($account);
        if ($paymentTerm) {
            $template = $event->getEnvironment()->render(
                'OroB2BPaymentBundle:Account:payment_term_view.html.twig',
                ['paymentTerm' => $paymentTerm]
            );
        } else {
            $accountGroupPaymentTerm = null;
            if ($account->getGroup()) {
                $accountGroupPaymentTerm =
                    $this->getPaymentRepository()->getOnePaymentTermByAccountGroup($account->getGroup());
            }

            $template = $event->getEnvironment()->render(
                'OroB2BPaymentBundle:Account:payment_term_for_account_view.html.twig',
                ['accountGroupPaymentTerm' => $accountGroupPaymentTerm]
            );
        }
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountGroupView(BeforeListRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $groupId = $this->request->get('id');
        /** @var AccountGroup $group */
        $group = $this->doctrineHelper->getEntityReference('OroB2BAccountBundle:AccountGroup', $groupId);
        $paymentTerm = $this->getPaymentRepository()->getOnePaymentTermByAccountGroup($group);

        $template = $event->getEnvironment()->render(
            'OroB2BPaymentBundle:Account:payment_term_view.html.twig',
            ['paymentTerm' => $paymentTerm]);
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPaymentBundle:Account:payment_term_update.html.twig',
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

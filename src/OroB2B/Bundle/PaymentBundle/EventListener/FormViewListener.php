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
     * @var string
     */
    protected $paymentTermClass;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper      $doctrineHelper
     * @param string              $paymentTermClass
     */
    public function __construct(TranslatorInterface $translator, DoctrineHelper $doctrineHelper, $paymentTermClass)
    {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTermClass = $paymentTermClass;
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

        $paymentTermRepository = $this->getPaymentTermRepository();
        $paymentTerm = $paymentTermRepository->getOnePaymentTermByAccount($account);

        if ($paymentTerm) {
            $paymentTermData = [
                'paymentTerm' => $paymentTerm,
                'paymentTermLabel' => $paymentTerm->getLabel(),
                'empty' => $this->translator->trans('N/A'),
                'defineToTheGroup' => false
            ];
            $template = $event->getEnvironment()->render(
                'OroB2BPaymentBundle:Account:payment_term_view.html.twig',
                ['paymentTermData' => $paymentTermData]
            );

        } else {
            $accountGroupPaymentTerm = null;

            $paymentTermLabelForAccount = $this->translator->trans(
                'orob2b.payment.account.payment_term_non_defined_in_group'
            );

            if ($account->getGroup()) {
                $accountGroupPaymentTerm = $paymentTermRepository
                    ->getOnePaymentTermByAccountGroup($account->getGroup());

                if ($accountGroupPaymentTerm) {
                    $paymentTermLabelForAccount = $this->translator->trans(
                        'orob2b.payment.account.payment_term_defined_in_group',
                        [
                            '{{ payment_term }}' => $accountGroupPaymentTerm->getLabel()
                        ]
                    );
                }
            }

            $paymentTermData = [
                'paymentTerm' => $accountGroupPaymentTerm,
                'paymentTermLabel' => $paymentTermLabelForAccount,
                'empty' => $paymentTermLabelForAccount,
                'defineToTheGroup' => true
            ];

            $template = $event->getEnvironment()->render(
                'OroB2BPaymentBundle:Account:payment_term_view.html.twig',
                ['paymentTermData' => $paymentTermData]
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
        $paymentTermRepository = $this->getPaymentTermRepository();
        $paymentTerm  = $paymentTermRepository->getOnePaymentTermByAccountGroup($group);

        $paymentTermData = [
            'paymentTerm' => $paymentTerm,
            'paymentTermLabel' => ($paymentTerm) ? $paymentTerm->getLabel() : null,
            'empty' => $this->translator->trans('N/A'),
            'defineToTheGroup' => false
        ];
        $template = $event->getEnvironment()->render(
            'OroB2BPaymentBundle:Account:payment_term_view.html.twig',
            ['paymentTermData' => $paymentTermData]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPaymentBundle:Account/Form:payment_term_update.html.twig',
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
    public function getPaymentTermRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->paymentTermClass);
    }
}

<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class DeleteMessageTextGenerator
{
    const ACCOUNT_GROUP_GRID_NAME = 'account-groups-grid';
    const ACCOUNT_GRID_NAME = 'account-accounts-grid';
    const ACCOUNT_GROUP_GRID_ROUTE = 'orob2b_account_group_index';
    const ACCOUNT_GRID_ROUTE = 'orob2b_account_index';

    /** @var RouterInterface  */
    protected $router;

    /** @var \Twig_Environment  */
    protected $twig;

    /** @var ManagerRegistry  */
    protected $managerRegistry;

    /**
     * @param RouterInterface $router
     * @param \Twig_Environment $twig
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(RouterInterface $router, \Twig_Environment $twig, ManagerRegistry $managerRegistry)
    {
        $this->router = $router;
        $this->twig = $twig;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    public function getDeleteMessageText(PaymentTerm $paymentTerm)
    {
        $accountGroupFilterUrlHtml = $this->generateAccountGroupFilterUrl($paymentTerm);
        $accountFilterUrlHtml = $this->generateAccountFilterUrl($paymentTerm);

        $message = $this->twig->render('@OroB2BPayment/PaymentTerm/deleteMessage.html.twig', [
            'accountFilterUrl' => $accountFilterUrlHtml,
            'accountGroupFilterUrl' => $accountGroupFilterUrlHtml
        ]);

        return $message;
    }

    /**
     * @param int $paymentTermId
     * @return string
     */
    public function getDeleteMessageTextForDataGrid($paymentTermId)
    {
        $paymentRepository = $this->managerRegistry
            ->getManagerForClass('OroB2BPaymentBundle:PaymentTerm')
            ->getRepository('OroB2BPaymentBundle:PaymentTerm');
        $paymentTerm = $paymentRepository->find($paymentTermId);

        return $this->getDeleteMessageText($paymentTerm);
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    protected function generateAccountGroupFilterUrl(PaymentTerm $paymentTerm)
    {
        if ($paymentTerm->getAccountGroups()->count() === 0) {
            return null;
        }

        return $this->generateHtmFilterUrl(
            $paymentTerm->getId(),
            static::ACCOUNT_GROUP_GRID_NAME,
            static::ACCOUNT_GROUP_GRID_ROUTE,
            'orob2b.account.accountgroup.entity_label'
        );
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    protected function generateAccountFilterUrl(PaymentTerm $paymentTerm)
    {
        if ($paymentTerm->getAccounts()->count() === 0) {
            return null;
        }

        return $this->generateHtmFilterUrl(
            $paymentTerm->getId(),
            static::ACCOUNT_GRID_NAME,
            static::ACCOUNT_GRID_ROUTE,
            'orob2b.account.entity_label'
        );
    }

    /**
     * @param string $gridName
     * @param int $paymentTermId
     * @return array
     */
    protected function getParameters($gridName, $paymentTermId)
    {
        $parameters = [
            $gridName => [
                OrmFilterExtension::FILTER_ROOT_PARAM => [
                    'payment_term_label' => [
                        'value' => $paymentTermId
                    ]
                ]
            ]
        ];

        return $parameters;
    }

    /**
     * @param int $paymentTermId
     * @param string $gridName
     * @param string $gridRoute
     * @param string $label
     * @return string
     */
    protected function generateHtmFilterUrl($paymentTermId, $gridName, $gridRoute, $label)
    {
        $urlParameters = $this->getParameters($gridName, $paymentTermId);
        $url = $this->router->generate($gridRoute, $urlParameters, true);
        $htmlFilterUrl = $this->twig->render('@OroB2BPayment/PaymentTerm/linkWithTarget.html.twig', [
            'urlPath' => $url,
            'label' => $label
        ]);

        return $htmlFilterUrl;
    }
}

<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\UIBundle\Twig\Environment;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class DeleteMessageTextGenerator
{
    const CUSTOMER_GROUP_GRID_NAME = 'customer-groups-grid';
    const CUSTOMER_GRID_NAME = 'customer-customers-grid';
    const CUSTOMER_GROUP_GRID_ROUTE = 'orob2b_customer_group_index';
    const CUSTOMER_GRID_ROUTE = 'orob2b_customer_index';

    /** @var RouterInterface  */
    protected $router;

    /** @var Environment  */
    protected $twig;

    public function __construct(RouterInterface $router, Environment $twig, ManagerRegistry $managerRegistry)
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
        $customerGroupFilterUrlHtml  = $this->generateCustomerGroupFilterUrl($paymentTerm);
        $customerFilterUrlHtml = $this->generateCustomerFilterUrl($paymentTerm);

        $message = $this->twig->render('@OroB2BPayment/PaymentTerm/deleteMessage.html.twig', [
            'customerFilterUrl' => $customerFilterUrlHtml,
            'customerGroupFilterUrl' => $customerGroupFilterUrlHtml
        ]);

        return $message;
    }

    public function getDeleteMessageTextForDataGrid($paymentTermId) {

        $paymentRepository = $this->managerRegistry
            ->getManagerForClass('OroB2BPaymentBundle:PaymentTerm')
            ->getRepository('OroB2BPaymentBundle:PaymentTerm');
        $paymentTerm = $paymentRepository->find($paymentTermId);

        $message = $this->getDeleteMessageText($paymentTerm);
        return $message;
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    private function generateCustomerGroupFilterUrl(PaymentTerm $paymentTerm)
    {
        if ($paymentTerm->getCustomerGroups()->count() == 0) {
            return null;
        }

        $customerGroupFilterHtmlUrl =
            $this->generateHtmFilterUrl(
                $paymentTerm->getId(),
                static::CUSTOMER_GROUP_GRID_NAME,
                static::CUSTOMER_GROUP_GRID_ROUTE,
                'orob2b.customer.customergroup.entity_label'
            );

        return $customerGroupFilterHtmlUrl;
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    private function generateCustomerFilterUrl(PaymentTerm $paymentTerm)
    {
        if ($paymentTerm->getCustomers()->count() == 0) {
            return null;
        }

        $customerFilterHtmlUrl = $this->generateHtmFilterUrl(
            $paymentTerm->getId(),
            static::CUSTOMER_GRID_NAME,
            static::CUSTOMER_GRID_ROUTE,
            'orob2b.customer.entity_label'
        );

        return $customerFilterHtmlUrl;

    }

    /**
     * @param $gridName
     * @param $paymentTermId
     * @return array
     */
    private function getParameters($gridName, $paymentTermId)
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

    private function generateHtmFilterUrl($paymentTermId, $gridName, $gridRoute, $label)
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

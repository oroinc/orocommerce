<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

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

    public function __construct(RouterInterface $router, Environment $twig)
    {
        $this->router = $router;
        $this->twig = $twig;
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

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    private function generateCustomerGroupFilterUrl(PaymentTerm $paymentTerm)
    {
        if ($paymentTerm->getCustomerGroups()->count() == 0) {
            return null;
        }

        $groupUrlParameters = $this->getParameters(static::CUSTOMER_GROUP_GRID_NAME, $paymentTerm);
        $groupFilterUrl = $this->router->generate(static::CUSTOMER_GROUP_GRID_ROUTE, $groupUrlParameters, true);
        $customerGroupFilterHtmlUrl = $this->twig->render('@OroB2BPayment/PaymentTerm/linkWithTarget.html.twig', [
            'urlPath' => $groupFilterUrl,
            'label' => 'orob2b.customer.customergroup.entity_label'
        ]);

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

        $customerUrlParameters = $this->getParameters(static::CUSTOMER_GRID_NAME, $paymentTerm);
        $customerFilterUrl = $this->router->generate(static::CUSTOMER_GRID_ROUTE, $customerUrlParameters, true);
        $customerFilterHtmlUrl = $this->twig->render('@OroB2BPayment/PaymentTerm/linkWithTarget.html.twig', [
            'urlPath' => $customerFilterUrl,
            'label' => 'orob2b.customer.entity_label'
        ]);

        return $customerFilterHtmlUrl;
    }

    /**
     * @param             $gridName
     * @param PaymentTerm $paymentTerm
     * @return array
     */
    private function getParameters($gridName, PaymentTerm $paymentTerm)
    {
        $parameters = [
            $gridName => [
                '_filter' => [
                    'payment_term_label' => [
                        'value' => $paymentTerm->getId()
                    ]
                ]
            ]
        ];

        return $parameters;
    }
}

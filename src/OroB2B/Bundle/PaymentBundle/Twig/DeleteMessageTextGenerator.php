<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Routing\RouterInterface;

use Twig_Environment;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class DeleteMessageTextExtension extends \Twig_Extension
{
    const CUSTOMER_GROUP_GRID_NAME = 'customer-groups-grid';
    const CUSTOMER_GRID_NAME = 'customer-customers-grid';
    const CUSTOMER_GROUP_GRID_ROUTE = 'orob2b_customer_group_index';
    const CUSTOMER_GRID_ROUTE = 'orob2b_customer_index';

    protected $router;

    /** @var  Twig_Environment */
    protected $environment;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Initializes the runtime environment.
     *
     * This is where you can load some file that contains filter functions for instance.
     *
     * @param Twig_Environment $environment The current Twig_Environment instance
     */
    public function initRuntime(Twig_Environment $environment)
    {
        $this->environment = $environment;
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'orob2b_payment_term_delete_message';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('getPaymentTermDeleteMessageText', [$this, 'getDeleteMessageText']),
        ];
    }

    public function getDeleteMessageText(PaymentTerm $paymentTerm)
    {
        $customerGroupFilterUrlHtml  = $this->generateCustomerGroupFilterUrl($paymentTerm);
        $customerFilterUrlHtml = $this->generateCustomerFilterUrl($paymentTerm);

        $message = $this->environment->render('@OroB2BPayment/PaymentTerm/deleteMessage.html.twig', [
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
        $customerGroupFilterHtmlUrl = $this->environment->render('@OroB2BPayment/PaymentTerm/linkWithTarget.html.twig', [
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
        $customerFilterHtmlUrl = $this->environment->render('@OroB2BPayment/PaymentTerm/linkWithTarget.html.twig', [
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

<?php

namespace OroB2B\Bundle\PaymentBundle\Entity;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Translation\Translator;

use Doctrine\Common\Persistence\ObjectManager;

class PaymentTermManager
{

    const CUSTOMER_GROUP_GRID_NAME = 'customer-groups-grid';
    const CUSTOMER_GRID_NAME = 'customer-customers-grid';
    const CUSTOMER_GROUP_GRID_ROUTE = 'orob2b_customer_group_index';
    const CUSTOMER_GRID_ROUTE = 'orob2b_customer_index';

    private $em;
    private $translator;
    private $router;

    /**
     * @param ObjectManager $entityManager
     * @param Translator    $translator
     * @param Router        $router
     */
    public function __construct(ObjectManager $entityManager, Translator $translator, Router $router)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * Generate message for confirmation popup on delete action
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    public function getDeleteMessageText(PaymentTerm $paymentTerm)
    {
        $message = $this->translator->trans(
            'oro.ui.delete_confirm',
            [
                '%entity_label%' => $this->translator->trans('orob2b.payment.paymentterm.entity_label')
            ]
        );

        $customerGroupsByPaymentTerm =
            $this->em->getRepository('OroB2BCustomerBundle:CustomerGroup')->findBy(['paymentTerm' => $paymentTerm]);

        $customersByPaymentTerm =
            $this->em->getRepository('OroB2BCustomerBundle:Customer')->findBy(['paymentTerm' => $paymentTerm]);

        if ($customerGroupsByPaymentTerm and $customersByPaymentTerm) {
            $customerGroupUrlHtml  = $this->generateCustomerGroupUrl($paymentTerm);
            $customerUrlHtml = $this->generateCustomerUrl($paymentTerm);
            $message .= $this->translator->trans(
                'orob2b.payment.controller.paymentterm.delete.with_two_url.message',
                [
                    '%accounts%' => $customerUrlHtml,
                    '%account_groups%' => $customerGroupUrlHtml,
                ]
            );
        } else {
            if ($customerGroupsByPaymentTerm) {
                $customerGroupUrlHtml  = $this->generateCustomerGroupUrl($paymentTerm);
                $message .= $this->translator->trans(
                    'orob2b.payment.controller.paymentterm.delete.with_url.message',
                    [
                        '%url%' => $customerGroupUrlHtml,
                    ]
                );
            }

            if ($customersByPaymentTerm) {
                $customerUrlHtml  = $this->generateCustomerUrl($paymentTerm);
                $message .= $this->translator->trans(
                    'orob2b.payment.controller.paymentterm.delete.with_url.message',
                    [
                        '%url%' => $customerUrlHtml,
                    ]
                );
            }
        }

        return $message;
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    private function generateCustomerGroupUrl(PaymentTerm $paymentTerm)
    {
        $groupUrlParameters = $this->getParameters(static::CUSTOMER_GROUP_GRID_NAME, $paymentTerm);
        $groupFilterUrl = $this->router->generate(static::CUSTOMER_GROUP_GRID_ROUTE, $groupUrlParameters, true);
        $groupUrlHtml  = '<a href="' . $groupFilterUrl . '" target="_blank">' .
            $this->translator->trans('orob2b.customer.customergroup.entity_plural_label') . '</a>';

        return $groupUrlHtml;
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    private function generateCustomerUrl(PaymentTerm $paymentTerm)
    {
        $customerUrlParameters = $this->getParameters(static::CUSTOMER_GRID_NAME, $paymentTerm);
        $customerFilterUrl = $this->router->generate(static::CUSTOMER_GRID_ROUTE, $customerUrlParameters, true);
        $customerUrlHtml  = '<a href="' . $customerFilterUrl . '" target="_blank">' .
            $this->translator->trans('orob2b.customer.entity_plural_label') . '</a>';

        return $customerUrlHtml;
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
                        'value' => $paymentTerm->getLabel()
                    ]
                ]
            ]
        ];

        return $parameters;
    }
}

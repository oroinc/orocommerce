<?php

namespace Oro\Bundle\PaymentTermBundle\Twig;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Manager\PaymentTermManager;

use Symfony\Component\Routing\RouterInterface;

class DeleteMessageTextGenerator
{
    const ACCOUNT_GROUP_GRID_NAME = 'account-groups-grid';
    const ACCOUNT_GRID_NAME = 'account-accounts-grid';
    const ACCOUNT_GROUP_GRID_ROUTE = 'oro_customer_account_group_index';
    const ACCOUNT_GRID_ROUTE = 'oro_customer_account_index';

    /** @var RouterInterface */
    protected $router;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var PaymentTermManager */
    private $paymentTermManager;

    /**
     * @param RouterInterface $router
     * @param \Twig_Environment $twig
     * @param PaymentTermManager $paymentTermManager
     */
    public function __construct(
        RouterInterface $router,
        \Twig_Environment $twig,
        PaymentTermManager $paymentTermManager
    ) {
        $this->router = $router;
        $this->twig = $twig;
        $this->paymentTermManager = $paymentTermManager;
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    public function getDeleteMessageText(PaymentTerm $paymentTerm)
    {
        $accountGroupFilterUrlHtml = $this->generateAccountGroupFilterUrl($paymentTerm);
        $accountFilterUrlHtml = $this->generateAccountFilterUrl($paymentTerm);

        $message = $this->twig->render(
            '@OroPaymentTerm/PaymentTerm/deleteMessage.html.twig',
            [
                'accountFilterUrl' => $accountFilterUrlHtml,
                'accountGroupFilterUrl' => $accountGroupFilterUrlHtml,
            ]
        );

        return $message;
    }

    /**
     * @param int $paymentTermId
     * @return string
     */
    public function getDeleteMessageTextForDataGrid($paymentTermId)
    {
        $paymentTerm = $this->paymentTermManager->getReference($paymentTermId);
        if (!$paymentTerm) {
            throw new \InvalidArgumentException(sprintf('PaymentTerm #%s not found', $paymentTermId));
        }

        return $this->getDeleteMessageText($paymentTerm);
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    protected function generateAccountGroupFilterUrl(PaymentTerm $paymentTerm)
    {
        if (!$this->paymentTermManager->hasAssignedPaymentTerm(AccountGroup::class, $paymentTerm)) {
            return null;
        }

        return $this->generateHtmFilterUrl(
            $paymentTerm->getId(),
            static::ACCOUNT_GROUP_GRID_NAME,
            static::ACCOUNT_GROUP_GRID_ROUTE,
            'oro.customer.accountgroup.entity_label'
        );
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    protected function generateAccountFilterUrl(PaymentTerm $paymentTerm)
    {
        if (!$this->paymentTermManager->hasAssignedPaymentTerm(Account::class, $paymentTerm)) {
            return null;
        }

        return $this->generateHtmFilterUrl(
            $paymentTerm->getId(),
            static::ACCOUNT_GRID_NAME,
            static::ACCOUNT_GRID_ROUTE,
            'oro.customer.account.entity_label'
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
            'grid' => [
                $gridName => http_build_query(
                    [
                        OrmFilterExtension::MINIFIED_FILTER_PARAM => [
                            $this->paymentTermManager->getAssociationName() => [
                                'value' => [$paymentTermId],
                            ],
                        ],
                    ]
                ),
            ],
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
        $htmlFilterUrl = $this->twig->render(
            '@OroPaymentTerm/PaymentTerm/linkWithTarget.html.twig',
            [
                'urlPath' => $url,
                'label' => $label,
            ]
        );

        return $htmlFilterUrl;
    }
}

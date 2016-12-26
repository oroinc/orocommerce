<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActionPermissionProvider
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var CheckoutRepository */
    protected $checkoutRepository;

    /**
     * @param SecurityFacade $securityFacade
     * @param CheckoutRepository $checkoutRepository
     */
    public function __construct(SecurityFacade $securityFacade, CheckoutRepository $checkoutRepository)
    {
        $this->securityFacade = $securityFacade;
        $this->checkoutRepository = $checkoutRepository;
    }

    /**
     * @param ResultRecord $record
     * @return array
     */
    public function getActionPermissions(ResultRecord $record)
    {
        $checkout = $this->checkoutRepository->find($record->getValue('id'));

        return [
            'view' => $checkout->getAccountUser() === $this->securityFacade->getLoggedUser(),
        ];
    }
}

<?php

namespace Oro\Bundle\SaleBundle\EventListener\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Checks user access to the `guest-frontend-quotes-line-items-grid` datagrid.
 */
class QuoteItemDatagridUserAccessListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ManagerRegistry $doctrine,
        private GuestQuoteAccessProvider $guestQuoteAccessProvider
    ) {
    }

    public function onResultBeforeQuery(OrmResultBeforeQuery $event): void
    {
        $datagrid = $event->getDatagrid();
        $quoteGuestAccessId = $datagrid->getParameters()->get('guest_access_id');
        if ($quoteGuestAccessId === null) {
            throw new AccessDeniedException();
        }
        $quote = $this->doctrine->getManagerForClass(Quote::class)
            ->getRepository(Quote::class)
            ->getQuoteByGuestAccessId($quoteGuestAccessId);
        if ($quote === null) {
            throw new AccessDeniedException();
        }

        if (
            $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken
            && $this->guestQuoteAccessProvider->isGranted($quote)
        ) {
            return;
        }

        /** @var OrmDatasource $dataSource */
        $dataSource = $datagrid->getDatasource();
        $qb = $dataSource->getQueryBuilder();
        $qb->andWhere('1 = 0');
    }
}

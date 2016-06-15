<?php

namespace OroB2B\Bundle\SaleBundle\Datagrid\CheckoutSource;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinitionResolverInterface;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinition;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\Translation\TranslatorInterface;

class QuoteCheckoutSourceDefinitionResolver implements CheckoutSourceDefinitionResolverInterface
{
    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade, TranslatorInterface $translator)
    {
        $this->securityFacade = $securityFacade;
        $this->translator = $translator;
    }

    /**
     * @param EntityManagerInterface $em
     * @param array $ids
     * @return array
     */
    public function loadSources(EntityManagerInterface $em, array $ids)
    {
        $databaseResults = $em->createQueryBuilder()
            ->select('c.id, q')
            ->from('OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout', 'c')
            ->join('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')
            ->join('OroB2B\Bundle\SaleBundle\Entity\QuoteDemand', 'qd', 'WITH', 's.quoteDemand = qd')
            ->join('OroB2B\Bundle\SaleBundle\Entity\Quote', 'q', 'WITH', 'qd.quote = q')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $result = [];

        foreach ($databaseResults as $databaseResult) {
            if (!isset($databaseResult[0])) {
                continue;
            }
            
            $quote = $databaseResult[0];
            
            if ($quote instanceof Quote) {
                $result[$databaseResult['id']] = new CheckoutSourceDefinition(
                    $this->translator->trans('orob2b.sale.quote.entity_label'),
                    $this->hasCurrentUserRightToView($quote),
                    'orob2b_sale_quote_frontend_view',
                    ['id' => $quote->getId()]
                );
            }
        }

        return $result;
    }

    /**
     * @param $quote
     * @return bool
     */
    private function hasCurrentUserRightToView($quote)
    {
        $isGranted = $this->securityFacade->isGranted('ACCOUNT_VIEW', $quote);

        return $isGranted === true || $isGranted === "true"; // isGranted may return "true" as string
    }
}

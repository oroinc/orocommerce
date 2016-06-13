<?php

namespace OroB2B\Bundle\ShoppingListBundle\Datagrid\CheckoutSource;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinerInterface;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinition;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListCheckoutSourceDefiner implements CheckoutSourceDefinerInterface
{
    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param EntityManagerInterface $em
     * @param array $ids
     * @return array
     */
    public function loadSources(EntityManagerInterface $em, array $ids)
    {
        $databaseResults = $em->createQueryBuilder()
            ->select('c.id, sl')
            ->from('OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout', 'c')
            ->join('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')
            ->join('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', 'sl', 'WITH', 's.shoppingList = sl')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $result = [];

        foreach ($databaseResults as $databaseResult) {
            $source = $databaseResult[0];
            
            if ($source instanceof ShoppingList) {
                $result[$databaseResult['id']] = new CheckoutSourceDefinition(
                    $source->getLabel(),
                    $this->hasCurrentUserRightToView($source),
                    'orob2b_shopping_list_frontend_view',
                    ['id' => $source->getId()]
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

<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataProvider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

class ShoppingListDataProvider
{
    /** @var  ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }


}

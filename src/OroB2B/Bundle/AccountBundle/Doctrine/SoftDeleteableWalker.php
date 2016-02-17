<?php

namespace OroB2B\Bundle\AccountBundle\Doctrine;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TreeWalker;
use Doctrine\ORM\Query\TreeWalkerAdapter;

class SoftDeleteableWalker extends TreeWalkerAdapter
{
    public function walkWhereClause($whereClause)
    {
        return $whereClause;
    }
}

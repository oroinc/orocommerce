<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractWebsiteSearchEngine;

class ORMEngine extends AbstractWebsiteSearchEngine
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * {@inheritdoc}
     */
    public function doSearch(Query $query, $context = [])
    {
        /**
         * TODO: It's only mock. Should be done in next tasks.
         */

        return [
            'results'       => [
                new Item($this->doctrine->getManager(), 'testEntity', '1', 'testTitle', 'testUrl', [])
            ],
            'records_count' => 1
        ];
    }

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
}

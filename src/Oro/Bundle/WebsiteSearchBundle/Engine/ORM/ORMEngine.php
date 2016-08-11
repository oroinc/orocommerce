<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractWebsiteSearchEngine;

class ORMEngine extends AbstractWebsiteSearchEngine
{
    /**
     * @var ObjectManager
     */
    private $em;

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
                new Item($this->em, 'testEntity', '1', 'testTitle', 'testUrl', [])
            ],
            'records_count' => 1
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function setEntityManager(ObjectManager $manager)
    {
        $this->em = $manager;
    }
}

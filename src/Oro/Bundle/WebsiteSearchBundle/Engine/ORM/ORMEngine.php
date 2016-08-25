<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractWebsiteSearchEngine;

class ORMEngine extends AbstractWebsiteSearchEngine
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * {@inheritdoc}
     */
    public function doSearch(Query $query, array $context = [])
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
     * @param RegistryInterface $doctrine
     */
    public function setDoctrine(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }
}

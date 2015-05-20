<?php

namespace OroB2B\Bundle\CustomerBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use OroB2B\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;

class ParentCustomerSearchHandler extends SearchHandler
{
    const DELIMITER = ';';

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        if (strpos($search, ';') === false) {
            return [];
        }
        list($searchTerm, $customerId) = $this->explodeSearchTerm($search);

        $entityIds = $this->searchIds($searchTerm, $firstResult, $maxResults);

        if ($customerId) {
            /** @var CustomerRepository $repository */
            $repository = $this->entityRepository;
            $children = $repository->getChildrenIds($this->aclHelper, $customerId);
            $entityIds = array_diff($entityIds, array_merge($children, [$customerId]));
        }

        $resultEntities = [];

        if ($entityIds) {
            $resultEntities = $this->getEntitiesByIds($entityIds);
        }

        return $resultEntities;
    }

    /**
     * @param string $search
     * @return array
     */
    protected function explodeSearchTerm($search)
    {
        $delimiterPos = strrpos($search, self::DELIMITER);
        $searchTerm = substr($search, 0, $delimiterPos);
        $customerId = substr($search, $delimiterPos + 1);
        if ($customerId === false) {
            $customerId = '';
        } else {
            $customerId = (int)$customerId;
        }

        return [$searchTerm, $customerId];
    }
}

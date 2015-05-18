<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use OroB2B\Bundle\CustomerAdminBundle\Entity\Repository\CustomerRepository;

class ParentCustomerSearchHandler extends SearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        if (strpos($search, ';') === false) {
            return [];
        }
        list($searchTerm, $customerId) = explode(';', $search);

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
}

<?php

namespace OroB2B\Bundle\AccountBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountRepository;

class ParentAccountSearchHandler extends SearchHandler
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
        list($searchTerm, $accountId) = $this->explodeSearchTerm($search);

        $entityIds = $this->searchIds($searchTerm, $firstResult, $maxResults);

        if ($accountId) {
            /** @var AccountRepository $repository */
            $repository = $this->entityRepository;
            $children = $repository->getChildrenIds($this->aclHelper, $accountId);
            $entityIds = array_diff($entityIds, array_merge($children, [$accountId]));
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
        $accountId = substr($search, $delimiterPos + 1);
        if ($accountId === false) {
            $accountId = '';
        } else {
            $accountId = (int)$accountId;
        }

        return [$searchTerm, $accountId];
    }
}

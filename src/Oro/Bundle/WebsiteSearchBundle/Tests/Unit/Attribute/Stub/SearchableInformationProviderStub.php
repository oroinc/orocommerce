<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Stub;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\WebsiteSearchBundle\Attribute\SearchableInformationProvider;

class SearchableInformationProviderStub extends SearchableInformationProvider
{
    /**
     * @var float|null
     */
    private $searchBoost;

    public function __construct()
    {
    }

    /**
     * @return float|null
     */
    public function getAttributeSearchBoost(FieldConfigModel $attribute): ?float
    {
        return $this->searchBoost;
    }

    /**
     * @param float|null $searchBoost
     *
     * @return float|null
     */
    public function setSearchBoost(?float $searchBoost): ?float
    {
        return $this->searchBoost = $searchBoost;
    }
}

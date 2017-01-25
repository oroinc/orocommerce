<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestProductItemsData;

/**
 * @dbIsolation
 */
class RequestProductItemApiTest extends AbstractRequestApiTest
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadRequestProductItemsData::class]);
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return RequestProductItem::class;
    }

    /**
     * @return array
     */
    public function cgetParamsAndExpectation()
    {
        return [
            [
                'filters' => [],
                'expectedCount' => 64,
                'params' => [],
                'expectedContent' => null,
            ],
        ];
    }
}

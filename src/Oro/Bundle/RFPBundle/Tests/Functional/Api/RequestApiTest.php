<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestApiTest extends AbstractRequestApiTest
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadRequestData::class]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityClass()
    {
        return Request::class;
    }

    /**
     * @return array
     */
    public function cgetParamsAndExpectation()
    {
        return [
            [
                'filters' => [],
                'expectedCount' => LoadRequestData::NUM_REQUESTS,
                'params' => [],
                'expectedContent' => null,
            ],
        ];
    }
}

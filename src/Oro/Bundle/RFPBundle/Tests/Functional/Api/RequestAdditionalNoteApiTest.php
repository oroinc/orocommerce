<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestAdditionalNoteApiTest extends AbstractRequestApiTest
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadRequestData::class]);
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return RequestAdditionalNote::class;
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

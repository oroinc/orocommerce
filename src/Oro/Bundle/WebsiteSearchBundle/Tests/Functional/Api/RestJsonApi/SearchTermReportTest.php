<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchTermReportData;

class SearchTermReportTest extends FeatureAwareRestJsonApiTestCase
{
    private const API_TYPE = 'searchtermreports';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadSearchTermReportData::class,
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => self::API_TYPE]
        );

        $this->assertResponseContains('cget_search_term_report.yml', $response, true);
    }

    public function testGet()
    {
        $id = $this->getReference('search_term_report_1')->getId();
        $response = $this->get(
            ['entity' => self::API_TYPE, 'id' => $id]
        );

        $this->assertResponseContains('get_search_term_report.yml', $response);
    }

    /**
     * @dataProvider relationshipDataProvider
     */
    public function testGetCustomerRelationship(
        string $resourceType,
        string $associationType
    ) {
        $getterMethod = 'get' . ucwords($associationType, '_');

        $record = $this->getReference('search_term_report_2');
        $id = $record->getId();

        $response = $this->getRelationship(
            ['entity' => self::API_TYPE, 'id' => $id, 'association' => $associationType]
        );

        $this->assertResponseContains(
            ['data' => ['type' => $resourceType, 'id' => (string)$record->{$getterMethod}()->getId()]],
            $response
        );
    }

    public function relationshipDataProvider(): array
    {
        return [
            'organization' => ['organizations', 'organization'],
            'owner' => ['businessunits', 'owner']
        ];
    }
}

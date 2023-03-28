<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryPart1Data;

class SearchResultHistoryTest extends FeatureAwareRestJsonApiTestCase
{
    private const API_TYPE = 'searchresulthistories';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadSearchResultHistoryPart1Data::class,
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => self::API_TYPE]
        );

        if (self::isEnterprise()) {
            $this->assertResponseContains('cget_search_result_history_ee.yml', $response, true);
        } else {
            $this->assertResponseContains('cget_search_result_history.yml', $response, true);
        }
    }

    public function testGet()
    {
        $id = $this->getReference('search_result_tires')->getId();
        $response = $this->get(
            ['entity' => self::API_TYPE, 'id' => $id]
        );

        if (self::isEnterprise()) {
            $this->assertResponseContains('get_search_result_history_ee.yml', $response);
        } else {
            $this->assertResponseContains('get_search_result_history.yml', $response);
        }
    }

    /**
     * @dataProvider relationshipDataProvider
     */
    public function testGetCustomerRelationship(
        string $resourceType,
        string $associationType
    ) {
        $getterMethod = 'get' . ucwords($associationType, '_');

        $record = $this->getReference('search_result_tires');
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
        $data = [
            'customer' => ['customers', 'customer'],
            'customerUser' => ['customerusers', 'customerUser'],
            'localization' => ['localizations', 'localization'],
            'organization' => ['organizations', 'organization'],
            'owner' => ['businessunits', 'owner']
        ];

        if (self::isEnterprise()) {
            $data['website'] = ['websites', 'website'];
        }

        return $data;
    }

    /**
     * EE response contains additional website relation, which is not available for CE version.
     */
    private static function isEnterprise(): bool
    {
        return class_exists('Oro\Bundle\MultiWebsiteBundle\OroMultiWebsiteBundle');
    }
}

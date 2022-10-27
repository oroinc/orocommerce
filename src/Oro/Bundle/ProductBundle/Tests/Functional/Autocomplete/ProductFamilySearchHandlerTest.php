<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductFamilyData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductFamilySearchHandlerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductFamilyData::class]);
    }

    public function testSearchById(): void
    {
        $family1 = $this->getReference(LoadProductFamilyData::PRODUCT_FAMILY_1);
        $family2 = $this->getReference(LoadProductFamilyData::PRODUCT_FAMILY_2);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_form_autocomplete_search',
                [
                    'name' => 'oro_product_families',
                    'search_by_id' => true,
                    'query' => implode(',', [$family2->getId(), $family1->getId()])
                ]
            )
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertResultContainsAttributeFamilies([$family1, $family2]);
    }

    public function testSearchWithoutQuery(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_form_autocomplete_search', ['name' => 'oro_product_families'])
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertResultContainsAttributeFamilies(
            [
                $this->getContainer()->get('doctrine')->getRepository(AttributeFamily::class)->find(1),
                $this->getReference(LoadProductFamilyData::PRODUCT_FAMILY_1),
                $this->getReference(LoadProductFamilyData::PRODUCT_FAMILY_2)
            ]
        );
    }

    public function testSearchWithQuery(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_form_autocomplete_search', ['name' => 'oro_product_families', 'query' => 'fam'])
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertResultContainsAttributeFamilies(
            [
                $this->getReference(LoadProductFamilyData::PRODUCT_FAMILY_1),
                $this->getReference(LoadProductFamilyData::PRODUCT_FAMILY_2)
            ]
        );
    }

    private function assertResultContainsAttributeFamilies(array $attributeFamilies): void
    {
        $expected = \array_map(
            function (AttributeFamily $attributeFamily) {
                return [
                    'id' => $attributeFamily->getId(),
                    'defaultLabel' => $attributeFamily->getDefaultLabel()
                ];
            },
            $attributeFamilies
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        static::assertFalse($response['more']);
        static::assertEquals($expected, $response['results']);
    }
}

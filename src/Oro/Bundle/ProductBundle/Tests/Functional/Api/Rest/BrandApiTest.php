<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class BrandApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
        $this->loadFixtures([LoadBrandData::class]);
    }

    public function testGetAction()
    {
        /** @var Brand $brand */
        $brand = self::getContainer()->get('doctrine')
            ->getRepository(Brand::class)
            ->findOneBy([]);

        $id = $brand->getId();

        $this->assertGreaterThan(0, $id);

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_brand', ['id' => $id]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteAction()
    {
        /** @var Brand $brand */
        $brand = self::getContainer()->get('doctrine')
            ->getRepository(Brand::class)
            ->findOneBy([]);

        $id = $brand->getId();

        $this->assertGreaterThan(0, $id);

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_brand', ['id' => $id]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->jsonRequest('DELETE', $this->getUrl('oro_api_delete_brand', ['id' => $id]));
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_brand', ['id' => $id]));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }
}

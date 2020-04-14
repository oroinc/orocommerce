<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @package Oro\Bundle\TaskBundle\Tests\Functional\Api
 */
class BrandApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], static::generateWsseAuthHeader());

        $this->loadFixtures([
            LoadBrandData::class
        ]);
    }

    public function testGetAction()
    {
        /** @var Brand $brand */
        $brand = $this
            ->getContainer()
            ->get('doctrine')
            ->getRepository('OroProductBundle:Brand')
            ->findOneBy([]);

        $id = $brand->getId();

        $this->assertGreaterThan(0, $id);

        $this->client->request('GET', $this->getUrl('oro_api_get_brand', ['id' => $id]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteAction()
    {
        /** @var Brand $brand */
        $brand = $this
            ->getContainer()
            ->get('doctrine')
            ->getRepository('OroProductBundle:Brand')
            ->findOneBy([]);

        $id = $brand->getId();

        $this->assertGreaterThan(0, $id);

        $this->client->request('GET', $this->getUrl('oro_api_get_brand', ['id' => $id]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('DELETE', $this->getUrl('oro_api_delete_brand', ['id' => $id]));
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', $this->getUrl('oro_api_get_brand', ['id' => $id]));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }
}

<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class WarehouseControllerTest extends WebTestCase
{
    const WAREHOUSE_TEST_NAME = 'Warehouse 1';
    const WAREHOUSE_UPDATED_TEST_NAME = 'Warehouse 11';

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_warehouse_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Warehouses', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_warehouse_create'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_warehouse' => [
                '_token' => $form['orob2b_warehouse[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'name' => self::WAREHOUSE_TEST_NAME
            ]
        ];

        $this->client->followRedirects(true);

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContentOnCreate($crawler, self::WAREHOUSE_TEST_NAME);

        $result = $this->getWarehouseDataByName(self::WAREHOUSE_TEST_NAME);

        return $result['id'];
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_warehouse_update', ['id' => $id]));

        $html = $crawler->html();
        $this->assertContains(self::WAREHOUSE_TEST_NAME, $html);

        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_warehouse' => [
                '_token' => $form['orob2b_warehouse[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'name' => self::WAREHOUSE_UPDATED_TEST_NAME
            ]
        ];

        $this->client->followRedirects(true);

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContentOnCreate($crawler, self::WAREHOUSE_UPDATED_TEST_NAME);

        $result = $this->getWarehouseDataByName(self::WAREHOUSE_UPDATED_TEST_NAME);

        $this->assertEquals($id, $result['id']);
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_warehouse', ['id' => $id]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_warehouse_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
    }

    /**
     * @param string $name
     *
     * @return array
     */
    protected function getWarehouseDataByName($name)
    {
        $response = $this->client->requestGrid(
            'warehouse-grid',
            [
                'warehouse-grid[_filter][name][value]' => $name
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);

        $result = reset($result['data']);
        $this->assertNotEmpty($result);

        return $result;
    }

    /**
     * @param Crawler $crawler
     * @param string $warehouseName
     */
    protected function assertContentOnCreate(Crawler $crawler, $warehouseName)
    {
        $html = $crawler->html();
        $this->assertContains('Warehouse has been saved', $html);
        $this->assertContains($warehouseName, $html);
        $this->assertEquals($warehouseName, $crawler->filter('h1.user-name')->html());
    }
}

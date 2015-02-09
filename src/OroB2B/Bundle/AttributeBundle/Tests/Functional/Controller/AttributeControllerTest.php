<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class AttributeControllerTest extends WebTestCase
{

    /**
     * @var string
     */
    protected $grid = 'orob2b-attribute-grid';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_attribute_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($this->grid, $crawler->html());
    }

    /**
     * @return int
     */
    public function testUpdate()
    {
        $this->markTestSkipped('Create/update not implemented yet');

        return 1;
    }

    /**
     * @depends testUpdate
     * @param int $id
     * @return int
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_attribute_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(/* some code . */' - Attributes - Product Management', $crawler->html());

        return $id;
    }

    /**
     * @depends testView
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request('DELETE', $this->getUrl('orob2b_api_delete_attribute', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_attribute_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }
}

<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class QuoteControllerTest extends WebTestCase
{
    public static $qid;
    public static $qidUpdated;
    
    public static $validUntil           = '2015-05-15T15:15:15+0000';
    public static $validUntilUpdated    = '2016-06-16T16:16:16+0000';

    public static function setUpBeforeClass()
    {
        self::$qid = 'TestQuoteID - ' . time() . '-' . rand();
        self::$qidUpdated = self::$qid . '- updated';
    }

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
    }
    
    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_index'));
        $result = $this->client->getResponse();
        
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("quotes-grid", $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_create'));
        $owner = $this->getContainer()->get('security.context')->getToken()->getUser();
        
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_sale_quote_form[owner]']      = $owner->getId();
        $form['orob2b_sale_quote_form[qid]']        = self::$qid;
        $form['orob2b_sale_quote_form[validUntil]'] = self::$validUntil;
        
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Quote has been saved", $crawler->html());
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'quotes-grid',
            ['quotes-grid[_filter][qid][value]' => self::$qid]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $this->assertEquals(self::$qid, $result['qid']);
        $this->assertEquals(self::$validUntil, $result['validUntil']);
        
        $id = $result['id'];
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_update', ['id' => $id]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_sale_quote_form[qid]'] = self::$qidUpdated;
        $form['orob2b_sale_quote_form[validUntil]'] = self::$validUntilUpdated;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Quote has been saved", $crawler->html());
        
        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     * @return int
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        
        return $id;
    }

    /**
     * @depends testView
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request('DELETE', $this->getUrl('orob2b_api_sale_delete_quote', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_sale_quote_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }
}

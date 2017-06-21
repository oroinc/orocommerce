<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Controller;

use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PromotionControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadPromotionData::class,
        ]);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_promotion_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('promotion-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_promotion_create'));
        $form = $crawler->selectButton('Close')->form();

        $segmentId = $this->getReference(LoadSegmentData::PRODUCT_STATIC_SEGMENT)->getId();
        $form['oro_promotion[owner]'] = $this->getOwnerId();
        $form['oro_promotion[rule][name]'] = 'Some name';
        $form['oro_promotion[rule][enabled]'] = 1;
        $form['oro_promotion[rule][sortOrder]'] = 100;
        $form['oro_promotion[rule][stopProcessing]'] = 1;
        $form['oro_promotion[rule][expression]'] = 'Some expression';
        $form['oro_promotion[useCoupons]'] = 1;
        $form['oro_promotion[schedules][0][activeAt]'] = '2016-03-01T22:00:00Z';
        $form['oro_promotion[schedules][0][deactivateAt]'] = '2016-03-01T22:00:00Z';
        $form['oro_promotion[productsSegment]'] = $segmentId;
        $form['oro_promotion[labels][values][default]'] = 'Default label';
        $form['oro_promotion[descriptions][values][default]'] = 'Default descriptions';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Promotion has been saved', $crawler->html());
    }

    public function testUpdate()
    {
        $promotionId = $this->getReference(LoadPromotionData::SIMPLE_PROMOTION)->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_promotion_update', ['id' => $promotionId]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_promotion[rule][name]'] = 'Some updated name';
        $form['oro_promotion[rule][sortOrder]'] = 10;
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Promotion has been saved', $crawler->html());
    }

    public function testView()
    {
        $promotionId = $this->getReference(LoadPromotionData::SIMPLE_PROMOTION)->getId();
        $this->client->request('GET', $this->getUrl('oro_promotion_view', ['id' => $promotionId]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @return int
     */
    protected function getOwnerId()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser()->getId();
    }
}

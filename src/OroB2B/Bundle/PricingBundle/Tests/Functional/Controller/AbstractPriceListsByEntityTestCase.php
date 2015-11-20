<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

abstract class AbstractPriceListsByEntityTestCase extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testDelete()
    {
        $this->assertCount(1, $this->getPriceListsByEntity());
        $crawler = $this->client->request(
            'GET',
            $this->getUpdateUrl()
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form();
        $priceListsByWebsitesPath = sprintf('%s[priceListsByWebsites]', $this->getMainFormName());
        $this->assertTrue(isset($form[$priceListsByWebsitesPath]));
        unset($form[$priceListsByWebsitesPath]);
        $x = $this->getPriceListsByEntity();
        $crawler = $this->client->submit($form);
        $x = $this->getPriceListsByEntity();
        $this->assertCount(0, $this->getPriceListsByEntity());
        $form = $crawler->selectButton('Save and Close')->form();
        $vals = $form->getValues();
        $this->client->followRedirects(true);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains($priceList->getName(), $crawler->html());
    }


    /**
     * @return BasePriceListRelation[]
     */
    abstract public function getPriceListsByEntity();

    /**
     * @return string
     */
    abstract public function getUpdateUrl();

    /**
     * @return string
     */
    abstract public function getMainFormName();


    /**
     * Will return Account or Account Group
     * @return object
     */
//    abstract public function getTargetEntity();
}
<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use OroB2B\Bundle\FrontendBundle\Test\WebTestCase;

class RequstControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testLol()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_create'));

        echo $crawler->html();
    }
}

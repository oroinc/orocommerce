<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolation
 */
class AccountGroupControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testUpdate()
    {
        /** @var AccountGroup $group */
        $group = $this->getReference('account_group.group1');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_group_update', ['id' => $group->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_account_group_type' => ['priceList' => $priceList->getId()]
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains($priceList->getName(), $crawler->html());
    }
}

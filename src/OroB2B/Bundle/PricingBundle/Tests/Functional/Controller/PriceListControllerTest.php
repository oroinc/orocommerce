<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class PriceListControllerTest extends WebTestCase
{
    const PRICE_LIST_NAME = 'oldPriceList';
    const PRICE_LIST_NAME_EDIT = 'newPriceList';
    const CURRENCY = 'USD';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData'
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_pricing_price_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains($this->getReference('price_list_1')->getName(), $crawler->html());
        $this->assertContains($this->getReference('price_list_2')->getName(), $crawler->html());
        $this->assertContains($this->getReference('price_list_3')->getName(), $crawler->html());
        $this->assertContains($this->getReference('price_list_4')->getName(), $crawler->html());
        $this->assertContains($this->getReference('price_list_5')->getName(), $crawler->html());
    }

    public function testProductPrices()
    {
        $response = $this->client->requestGrid(
            'pricing-price-list-grid',
            ['pricing-price-list-grid[_filter][name][value]' => 'priceList1']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('priceList1', $crawler->html());

        $productPriceGrid = json_decode($crawler->filter('.inner-grid')->last()->attr('data-page-component-options'));
        $gridData = $productPriceGrid->data->data;

        $priceList = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceList')
            ->findOneBy(['name' => 'priceList1']);

        $prices = $priceList->getPrices();

        foreach ($prices as $price) {
            $gridPrice = array_shift($gridData);
            $this->assertEquals($price->getProduct()->getSku(), $gridPrice->productSku);
            $this->assertEquals($price->getQuantity(), $gridPrice->quantity);
            $this->assertContains($price->getUnit()->getCode(), $gridPrice->unitCode);
            $this->assertEquals($price->getPrice()->getValue(), $gridPrice->value);
            $this->assertEquals($price->getPrice()->getCurrency(), $gridPrice->currency);
        }
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_pricing_price_list_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_pricing_price_list[name]' => self::PRICE_LIST_NAME,
                'orob2b_pricing_price_list[appendCustomers]' => implode(
                    ',',
                    [$this->getReference('customer.orphan')->getId(), $this->getReference('customer.level_1')->getId()]
                ),
                'orob2b_pricing_price_list[appendCustomerGroups]' => implode(
                    ',',
                    [
                        $this->getReference('customer_group.group1')->getId(),
                        $this->getReference('customer_group.group2')->getId()
                    ]
                ),
                'orob2b_pricing_price_list[appendWebsites]' => implode(',', [$this->getReference('US')->getId()])
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Price List has been saved', $html);
        $customersGrid = $crawler->filter('.inner-grid')->eq(0)->attr('data-page-component-options');
        $this->assertContains($this->getReference('customer.orphan')->getName(), $customersGrid);
        $this->assertContains($this->getReference('customer.level_1')->getName(), $customersGrid);

        $customersGroupGrid = $crawler->filter('.inner-grid')->eq(1)->attr('data-page-component-options');
        $this->assertContains($this->getReference('customer_group.group1')->getName(), $customersGroupGrid);
        $this->assertContains($this->getReference('customer_group.group2')->getName(), $customersGroupGrid);

        $websitesGrid = $crawler->filter('.inner-grid')->eq(2)->attr('data-page-component-options');
        $this->assertContains($this->getReference('US')->getName(), $websitesGrid);
    }

    /**
     * @return int
     *
     * @depends testCreate
     */
    public function testView()
    {
        $response = $this->client->requestGrid(
            'pricing-price-list-grid',
            ['pricing-price-list-grid[_filter][name][value]' => self::PRICE_LIST_NAME]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(self::PRICE_LIST_NAME, $crawler->html());

        return $id;
    }

    /**
     * @param int $id
     * @return int
     *
     * @depends testView
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_update', ['id' => $id])
        );

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_pricing_price_list[name]' => self::PRICE_LIST_NAME_EDIT,
                'orob2b_pricing_price_list[currencies]' => self::CURRENCY,
                'orob2b_pricing_price_list[appendCustomers]' => $this->getReference('customer.level_1.1')->getId(),
                'orob2b_pricing_price_list[appendCustomerGroups]' => $this
                    ->getReference('customer_group.group3')->getId(),
                'orob2b_pricing_price_list[appendWebsites]' => $this->getReference('Canada')->getId(),
                'orob2b_pricing_price_list[removeCustomers]' => $this->getReference('customer.orphan')->getId(),
                'orob2b_pricing_price_list[removeCustomerGroups]' => implode(
                    ',',
                    [
                        $this->getReference('customer_group.group1')->getId(),
                        $this->getReference('customer_group.group2')->getId()
                    ]
                ),
                'orob2b_pricing_price_list[removeWebsites]' => $this->getReference('US')->getId()
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(self::PRICE_LIST_NAME_EDIT, $crawler->html());
        $this->assertContains(Intl::getCurrencyBundle()->getCurrencyName(self::CURRENCY), $crawler->html());

        $customersGrid = $crawler->filter('.inner-grid')->eq(0)->attr('data-page-component-options');
        $this->assertContains($this->getReference('customer.level_1')->getName(), $customersGrid);
        $this->assertContains($this->getReference('customer.level_1.1')->getName(), $customersGrid);
        $this->assertNotContains($this->getReference('customer.orphan')->getName(), $customersGrid);

        $customersGroupGrid = $crawler->filter('.inner-grid')->eq(1)->attr('data-page-component-options');
        $this->assertContains($this->getReference('customer_group.group3')->getName(), $customersGroupGrid);
        $this->assertNotContains($this->getReference('customer_group.group1')->getName(), $customersGroupGrid);
        $this->assertNotContains($this->getReference('customer_group.group2')->getName(), $customersGroupGrid);

        $websitesGrid = $crawler->filter('.inner-grid')->eq(2)->attr('data-page-component-options');
        $this->assertContains($this->getReference('Canada')->getName(), $websitesGrid);
        $this->assertNotContains($this->getReference('US')->getName(), $websitesGrid);

        return $id;
    }

    /**
     * @param int $id
     *
     * @depends testUpdate
     */
    public function testInfo($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_info', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::PRICE_LIST_NAME_EDIT, $crawler->html());
        $this->assertContains(Intl::getCurrencyBundle()->getCurrencyName(self::CURRENCY), $crawler->html());
    }
}

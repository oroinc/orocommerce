<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolation
 */
class PriceListControllerTest extends WebTestCase
{
    const PRICE_LIST_NAME = 'oldPriceList';
    const PRICE_LIST_NAME_EDIT = 'newPriceList';
    const CURRENCY = 'USD';
    const ADD_NOTE_BUTTON_NAME = 'Add note';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListSchedules',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_pricing_price_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('pricing-price-list-grid', $crawler->html());

        $this->assertContains($this->getPriceList('price_list_1')->getName(), $crawler->html());
        $this->assertContains($this->getPriceList('price_list_2')->getName(), $crawler->html());
        $this->assertContains($this->getPriceList('price_list_3')->getName(), $crawler->html());
        $this->assertContains($this->getPriceList('price_list_4')->getName(), $crawler->html());
        $this->assertContains($this->getPriceList('price_list_5')->getName(), $crawler->html());
    }

    /**
     * @dataProvider dataGridFiltersDataProvider
     * @param string $activity
     * @param string[] $priceLists
     */
    public function testDataGridFilters($activity, $priceLists)
    {
        $grid = $this->client->requestGrid(
            ['gridName' => 'pricing-price-list-grid'],
            [
                'pricing-price-list-grid[_filter][activity][value]' => $activity
            ]
        );
        $data = json_decode($grid->getContent(), true)['data'];
        $this->assertCount(count($priceLists), $data);
        foreach ($data as $priceList) {
            $this->assertContains($priceList['name'], $priceLists);
        }
    }

    /**
     * @return array
     */
    public function dataGridFiltersDataProvider()
    {
        return [
            'active' => [
                'activity' => 'active',
                'priceLists' => ['priceList1', 'priceList3', 'priceList5', 'Default Price List']
            ],
            'inactive' => [
                'activity' => 'inactive',
                'priceLists' => ['priceList2', 'priceList4']
            ]
        ];
    }

    public function testDataGridSorters()
    {
        $grid = $this->client->requestGrid(
            ['gridName' => 'pricing-price-list-grid'],
            ['pricing-price-list-grid[_sort_by][activity]' => OrmSorterExtension::DIRECTION_ASC]
        );
        $data = json_decode($grid->getContent(), true)['data'];
        $this->assertCount(7, $data);
        $this->assertEquals('Active', $data[0]['activity']);

        $grid = $this->client->requestGrid(
            ['gridName' => 'pricing-price-list-grid'],
            ['pricing-price-list-grid[_sort_by][activity]' => OrmSorterExtension::DIRECTION_DESC]
        );
        $data = json_decode($grid->getContent(), true)['data'];
        $this->assertCount(7, $data);
        $this->assertEquals('Inactive', $data[0]['activity']);
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_pricing_price_list_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_pricing_price_list[name]' => self::PRICE_LIST_NAME,
                'orob2b_pricing_price_list[schedules][0][activeAt]' => '2016-03-01T22:00:00Z',
                'orob2b_pricing_price_list[schedules][0][deactivateAt]' => '2016-03-15T22:00:00Z'
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Price List has been saved', $html);

        /** @var PriceList $priceList */
        $priceList = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceList')
            ->getRepository('OroB2BPricingBundle:PriceList')
            ->findOneBy(['name' => self::PRICE_LIST_NAME]);
        $this->assertNotEmpty($priceList);

        return $priceList->getId();
    }

    /**
     * @param $id int
     * @return int
     *
     * @depends testCreate
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(self::PRICE_LIST_NAME, $crawler->html());
        $this->assertContains(self::ADD_NOTE_BUTTON_NAME, $crawler->html());

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
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(self::PRICE_LIST_NAME_EDIT, $crawler->html());
        $this->assertContains(Intl::getCurrencyBundle()->getCurrencyName(self::CURRENCY), $crawler->html());

        return $id;
    }

    public function testUpdateCurrenciesError()
    {
        $id = $this->getReference('product_price.11')->getPriceList()->getId();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_update', ['id' => $id])
        );

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_pricing_price_list[currencies]' => ['USD'],
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $message = $this->getContainer()->get('translator')
            ->trans(
                'orob2b.pricing.validators.price_list.product_price_currency.message',
                ['%invalidCurrency%' => 'EUR'],
                'validators'
            );

        $this->assertContains($message, $crawler->html());
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
            $this->getUrl('orob2b_pricing_price_list_info', ['id' => $id]),
            ['_widgetContainer' => 'widget']
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::PRICE_LIST_NAME_EDIT, $crawler->html());
        $this->assertContains(Intl::getCurrencyBundle()->getCurrencyName(self::CURRENCY), $crawler->html());
    }

    /**
     * @param string $reference
     *
     * @return PriceList
     */
    protected function getPriceList($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param string $reference
     *
     * @return Account
     */
    protected function getAccount($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param string $reference
     *
     * @return AccountGroup
     */
    protected function getAccountGroup($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param string $reference
     *
     * @return Website
     */
    protected function getWebsite($reference)
    {
        return $this->getReference($reference);
    }
}

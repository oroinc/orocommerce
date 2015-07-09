<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class CustomerGroupControllerTest extends WebTestCase
{
    const NAME = 'Group_name';
    const UPDATED_NAME = 'Group_name_UP';
    const ADD_NOTE_BUTTON = 'Add note';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->entityManager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BCustomerBundle:CustomerGroup');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups'
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_customer_group_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_group_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertCustomerGroupSave(
            $crawler,
            self::NAME,
            [
                $this->getReference('customer.level_1.1'),
                $this->getReference('customer.level_1.2')
            ]
        );
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $id = $this->getGroupId(self::NAME);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_customer_group_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertCustomerGroupSave(
            $crawler,
            self::UPDATED_NAME,
            [
                $this->getReference('customer.level_1.1.1')
            ],
            [
                $this->getReference('customer.level_1.2')
            ]
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_customer_group_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains(self::UPDATED_NAME . ' - Customer Groups - Customers', $html);
        $this->assertContains(self::ADD_NOTE_BUTTON, $html);
        $this->assertViewPage($html, self::UPDATED_NAME);
    }

    /**
     * @param Crawler $crawler
     * @param string $name
     * @param Customer[] $appendCustomers
     * @param Customer[] $removeCustomers
     */
    protected function assertCustomerGroupSave(
        Crawler $crawler,
        $name,
        array $appendCustomers = [],
        array $removeCustomers = []
    ) {
        $appendCustomerIds = array_map(
            function (Customer $customer) {
                return $customer->getId();
            },
            $appendCustomers
        );
        $removeCustomerIds = array_map(
            function (Customer $customer) {
                return $customer->getId();
            },
            $removeCustomers
        );
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_customer_group_type[name]' => $name,
                'orob2b_customer_group_type[appendCustomers]' => implode(',', $appendCustomerIds),
                'orob2b_customer_group_type[removeCustomers]' => implode(',', $removeCustomerIds)
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Customer group has been saved', $html);
        $this->assertViewPage($html, $name);

        foreach ($appendCustomers as $customer) {
            $this->assertContains($customer->getName(), $html);
        }
        foreach ($removeCustomers as $customer) {
            $this->assertNotContains($customer->getName(), $html);
        }
    }

    /**
     * @param string $html
     * @param string $name
     */
    protected function assertViewPage($html, $name)
    {
        $this->assertContains($name, $html);
    }

    /**
     * @param string $name
     * @return int
     */
    protected function getGroupId($name)
    {
        $response = $this->client->requestGrid(
            'customer-groups-grid',
            ['customer-customers-grid[_filter][name][value]' => $name]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        return (int)$result['id'];
    }
}

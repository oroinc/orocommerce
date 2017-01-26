<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class CustomerControllerTest extends WebTestCase
{
    const ACCOUNT_NAME = 'Customer_name';
    const UPDATED_NAME = 'Customer_name_UP';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            $this->getFixtureList()
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('customer-customers-grid', $crawler->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Customer $parent */
        $parent = $this->getReference('customer.level_1');
        /** @var CustomerGroup $group */
        $group = $this->getReference('customer_group.group1');
        /** @var AbstractEnumValue $internalRating */
        $internalRating = $this->getReference('internal_rating.1 of 5');
        $this->assertCustomerSave($crawler, self::ACCOUNT_NAME, $parent, $group, $internalRating);

        /** @var Customer $customer */
        $customer = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:Customer')
            ->getRepository('OroCustomerBundle:Customer')
            ->findOneBy(['name' => self::ACCOUNT_NAME]);
        $this->assertNotEmpty($customer);

        return $customer->getId();
    }

    /**
     * @param int $id
     * @return int
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Customer $newParent */
        $newParent = $this->getReference('customer.level_1.1');
        /** @var CustomerGroup $newGroup */
        $newGroup = $this->getReference('customer_group.group2');
        /** @var AbstractEnumValue $internalRating */
        $internalRating = $this->getReference('internal_rating.2 of 5');
        $this->assertCustomerSave($crawler, self::UPDATED_NAME, $newParent, $newGroup, $internalRating);

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
            $this->getUrl('oro_customer_customer_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains(self::UPDATED_NAME . ' - Customers - Customers', $html);
        $this->assertContains('Add attachment', $html);
        $this->assertContains('Add note', $html);
        $this->assertContains('Address Book', $html);
        /** @var Customer $newParent */
        $newParent = $this->getReference('customer.level_1.1');
        /** @var CustomerGroup $newGroup */
        $newGroup = $this->getReference('customer_group.group2');
        /** @var AbstractEnumValue $internalRating */
        $internalRating = $this->getReference('internal_rating.2 of 5');
        $this->assertViewPage($html, self::UPDATED_NAME, $newParent, $newGroup, $internalRating);
    }

    /**
     * @param Crawler           $crawler
     * @param string            $name
     * @param Customer           $parent
     * @param CustomerGroup      $group
     * @param AbstractEnumValue $internalRating
     */
    protected function assertCustomerSave(
        Crawler $crawler,
        $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating
    ) {
        $form = $crawler->selectButton('Save and Close')->form(
            $this->prepareFormValues($name, $parent, $group, $internalRating)
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Customer has been saved', $html);
        $this->assertViewPage($html, $name, $parent, $group, $internalRating);
        $this->assertContains($this->getReference(LoadUserData::USER1)->getFullName(), $result->getContent());
        $this->assertContains($this->getReference(LoadUserData::USER2)->getFullName(), $result->getContent());
    }

    /**
     * @param string $html
     * @param string $name
     * @param Customer $parent
     * @param CustomerGroup $group
     * @param AbstractEnumValue $internalRating
     */
    protected function assertViewPage(
        $html,
        $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating
    ) {
        $this->assertContains($name, $html);
        $this->assertContains($parent->getName(), $html);
        $this->assertContains($group->getName(), $html);
        $this->assertContains($internalRating->getName(), $html);
    }

    /**
     * @param $name
     * @param Customer $parent
     * @param CustomerGroup $group
     * @param AbstractEnumValue $internalRating
     *
     * @return array
     */
    protected function prepareFormValues(
        $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating
    ) {
        return [
            'oro_customer_type[name]' => $name,
            'oro_customer_type[parent]' => $parent->getId(),
            'oro_customer_type[group]' => $group->getId(),
            'oro_customer_type[internal_rating]' => $internalRating->getId(),
            'oro_customer_type[salesRepresentatives]' => implode(',', [
                $this->getReference(LoadUserData::USER1)->getId(),
                $this->getReference(LoadUserData::USER2)->getId()
            ])
        ];
    }

    /**
     * @return array
     */
    protected function getFixtureList()
    {
        return [
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadInternalRating',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadUserData'
        ];
    }
}

<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

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
        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_1);

        $this->assertCustomerSave($crawler, self::ACCOUNT_NAME, $parent, $group, $internalRating, $customerTaxCode);

        /** @var Customer $taxCustomer */
        $taxCustomer = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:Customer')
            ->getRepository('OroCustomerBundle:Customer')
            ->findOneBy(['name' => self::ACCOUNT_NAME]);
        $this->assertNotEmpty($taxCustomer);

        return $taxCustomer->getId();
    }

    /**
     * @param $id int
     * @depends testCreate
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

        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_1);

        $this->assertContains($customerTaxCode->getCode(), $html);
    }

    /**
     * @depends testView
     */
    public function testTaxCodeViewContainsEntity()
    {
        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_customer_tax_code_view', ['id' => $customerTaxCode->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $grid = $crawler->filter('.inner-grid')->eq(0)->attr('data-page-component-options');
        $this->assertContains(self::ACCOUNT_NAME, $grid);
    }

    /**
     * @depends testTaxCodeViewContainsEntity
     */
    public function testGrid()
    {
        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_1);

        $response = $this->client->requestGrid(
            'customer-customers-grid',
            ['customer-customers-grid[_filter][name][value]' => self::ACCOUNT_NAME]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertArrayHasKey('taxCode', $result);
        $this->assertArrayHasKey('customerGroupTaxCode', $result);
        $this->assertEquals($customerTaxCode->getCode(), $result['taxCode']);
        $this->assertNull($result['customerGroupTaxCode']);
    }

    /**
     * @depends testGrid
     */
    public function testGridCustomerTaxCodeFallbackToCustomerGroup()
    {
        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_2);

        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1.2');

        $response = $this->client->requestGrid(
            'customer-customers-grid',
            ['customer-customers-grid[_filter][name][value]' => $customer->getName()]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertArrayHasKey('taxCode', $result);
        $this->assertEmpty($result['taxCode']);

        $this->assertArrayHasKey('customerGroupTaxCode', $result);
        $this->assertEquals($customerTaxCode->getCode(), $result['customerGroupTaxCode']);
    }

    /**
     * @depends testGridCustomerTaxCodeFallbackToCustomerGroup
     */
    public function testViewCustomerTaxCodeFallbackToCustomerGroup()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1.2');

        $response = $this->client->requestGrid(
            'customer-customers-grid',
            ['customer-customers-grid[_filter][name][value]' => $customer->getName()]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_2);

        $this->assertContains($customerTaxCode->getCode(), $html);
        $this->assertContains('(Defined for Customer Group)', $html);
    }

    /**
     * @param Crawler $crawler
     * @param string $name
     * @param Customer $parent
     * @param CustomerGroup $group
     * @param AbstractEnumValue $internalRating
     * @param CustomerTaxCode $customerTaxCode
     */
    protected function assertCustomerSave(
        Crawler $crawler,
        $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating,
        CustomerTaxCode $customerTaxCode
    ) {
        $form = $crawler->selectButton('Save and Close')->form(
            $this->getFormValues($name, $parent, $group, $internalRating, $customerTaxCode)
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Customer has been saved', $html);
        $this->assertViewPage($html, $name, $parent, $group, $internalRating, $customerTaxCode);
    }

    /**
     * @param string $html
     * @param string $name
     * @param Customer $parent
     * @param CustomerGroup $group
     * @param AbstractEnumValue $internalRating
     * @param CustomerTaxCode $customerTaxCode
     */
    protected function assertViewPage(
        $html,
        $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating,
        CustomerTaxCode $customerTaxCode
    ) {
        $groupName = $group->getName();
        $this->assertContains($name, $html);
        $this->assertContains($parent->getName(), $html);
        $this->assertContains($groupName, $html);
        $this->assertContains($internalRating->getName(), $html);
        $this->assertContains($customerTaxCode->getCode(), $html);
    }

    /**
     * @param string $name
     * @param Customer $parent
     * @param CustomerGroup $group
     * @param AbstractEnumValue $internalRating
     * @param CustomerTaxCode $customerTaxCode
     *
     * @return array
     */
    protected function getFormValues(
        $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating,
        CustomerTaxCode $customerTaxCode
    ) {
        return [
            'oro_customer_type[name]' => $name,
            'oro_customer_type[parent]' => $parent->getId(),
            'oro_customer_type[group]' => $group->getId(),
            'oro_customer_type[internal_rating]' => $internalRating->getId(),
            'oro_customer_type[taxCode]' => $customerTaxCode->getId(),
        ];
    }

    /**
     * @return array
     */
    protected function getFixtureList()
    {
        return [
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadInternalRating',
            'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes',
        ];
    }
}

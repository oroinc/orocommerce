<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadInternalRating;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class CustomerControllerTest extends WebTestCase
{
    private const CUSTOMER_NAME = 'Customer_name';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures($this->getFixtureList());
    }

    public function testCreate(): int
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

        $this->assertCustomerSave($crawler, self::CUSTOMER_NAME, $parent, $group, $internalRating, $customerTaxCode);

        /** @var Customer $taxCustomer */
        $taxCustomer = $this->getContainer()->get('doctrine')
            ->getRepository(Customer::class)
            ->findOneBy(['name' => self::CUSTOMER_NAME]);
        $this->assertNotEmpty($taxCustomer);

        return $taxCustomer->getId();
    }

    /**
     * @depends testCreate
     */
    public function testView(int $id)
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

        self::assertStringContainsString($customerTaxCode->getCode(), $html);

        $customerTaxCodeLink = $this->getContainer()->get('router')->generate('oro_tax_customer_tax_code_view', [
            'id' => $customerTaxCode->getId(),
        ]);

        self::assertStringContainsString($customerTaxCodeLink, $html);
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
        self::assertStringContainsString(self::CUSTOMER_NAME, $grid);
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
            ['customer-customers-grid[_filter][name][value]' => self::CUSTOMER_NAME]
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

        self::assertStringContainsString($customerTaxCode->getCode(), $html);
        self::assertStringContainsString('(Defined for Customer Group)', $html);
    }

    private function assertCustomerSave(
        Crawler $crawler,
        string $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating,
        CustomerTaxCode $customerTaxCode
    ): void {
        $form = $crawler->selectButton('Save and Close')->form(
            $this->getFormValues($name, $parent, $group, $internalRating, $customerTaxCode)
        );
        $redirectAction = $crawler->selectButton('Save and Close')->attr('data-action');
        $form->setValues(['input_action' => $redirectAction]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString('Customer has been saved', $html);
        $this->assertViewPage($html, $name, $parent, $group, $internalRating, $customerTaxCode);
    }

    private function assertViewPage(
        string $html,
        string $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating,
        CustomerTaxCode $customerTaxCode
    ): void {
        $groupName = $group->getName();
        self::assertStringContainsString($name, $html);
        self::assertStringContainsString($parent->getName(), $html);
        self::assertStringContainsString($groupName, $html);
        self::assertStringContainsString($internalRating->getName(), $html);
        self::assertStringContainsString($customerTaxCode->getCode(), $html);
    }

    protected function getFormValues(
        string $name,
        Customer $parent,
        CustomerGroup $group,
        AbstractEnumValue $internalRating,
        CustomerTaxCode $customerTaxCode
    ): array {
        return [
            'oro_customer_type[name]' => $name,
            'oro_customer_type[parent]' => $parent->getId(),
            'oro_customer_type[group]' => $group->getId(),
            'oro_customer_type[internal_rating]' => $internalRating->getId(),
            'oro_customer_type[taxCode]' => $customerTaxCode->getId(),
        ];
    }

    protected function getFixtureList(): array
    {
        return [
            LoadCustomers::class,
            LoadInternalRating::class,
            LoadCustomerTaxCodes::class,
        ];
    }
}

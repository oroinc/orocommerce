<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CustomerGroupControllerTest extends WebTestCase
{
    private const ACCOUNT_GROUP_NAME = 'Customer_Group';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadGroups::class, LoadCustomerTaxCodes::class]);
    }

    public function testCreate(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_group_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_1);

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_customer_group_type[name]' => self::ACCOUNT_GROUP_NAME,
                'oro_customer_group_type[taxCode]' => $customerTaxCode->getId(),
            ]
        );
        $redirectAction = $crawler->selectButton('Save and Close')->attr('data-action');
        $form->setValues(['input_action' => $redirectAction]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString('Customer group has been saved', $html);
        self::assertStringContainsString(self::ACCOUNT_GROUP_NAME, $html);
        self::assertStringContainsString($customerTaxCode->getCode(), $html);

        /** @var CustomerGroup $taxCustomerGroup */
        $taxCustomerGroup = $this->getContainer()->get('doctrine')
            ->getRepository(CustomerGroup::class)
            ->findOneBy(['name' => self::ACCOUNT_GROUP_NAME]);
        $this->assertNotEmpty($taxCustomerGroup);

        return $taxCustomerGroup->getId();
    }

    /**
     * @depends testCreate
     */
    public function testView(int $id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_group_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_1);

        self::assertStringContainsString($customerTaxCode->getCode(), $html);
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

        $grid = $crawler->filter('.inner-grid')->eq(1)->attr('data-page-component-options');
        self::assertStringContainsString(self::ACCOUNT_GROUP_NAME, $grid);
    }

    /**
     * @depends testTaxCodeViewContainsEntity
     */
    public function testGrid()
    {
        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX.'.'.LoadCustomerTaxCodes::TAX_1);

        $response = $this->client->requestGrid(
            'customer-groups-grid',
            ['customer-groups-grid[_filter][name][value]' => self::ACCOUNT_GROUP_NAME]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertArrayHasKey('taxCode', $result);
        $this->assertEquals($customerTaxCode->getCode(), $result['taxCode']);
    }
}

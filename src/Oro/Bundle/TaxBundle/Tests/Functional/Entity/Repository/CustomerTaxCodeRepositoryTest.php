<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes as TaxFixture;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerTaxCodeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(['Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes']);
    }

    public function testFindOneByCustomer()
    {
        /** @var Customer $customer1 */
        $customer1 = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        $expectedTaxCode = $this->getRepository()->findOneByCustomer($customer1);

        /** @var CustomerTaxCode $taxCode1 */
        $taxCode1 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);
        $this->assertEquals($expectedTaxCode->getId(), $taxCode1->getId());
    }

    public function testFindNewCustomer()
    {
        $this->assertEmpty($this->getRepository()->findOneByCustomer(new Customer()));
    }

    public function testFindByCodes()
    {
        /** @var CustomerTaxCode $taxCode1 */
        $taxCode = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);

        $this->assertEquals([$taxCode], $this->getRepository()->findByCodes([TaxFixture::TAX_1]));
    }

    public function testFindOneByCustomerGroup()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference(LoadGroups::GROUP2);
        $expectedTaxCode = $this->getRepository()->findOneByCustomerGroup($customerGroup);

        /** @var CustomerTaxCode $taxCode */
        $taxCode = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_2);
        $this->assertEquals($expectedTaxCode->getId(), $taxCode->getId());
    }

    public function testFindNewCustomerGroup()
    {
        $this->assertEmpty($this->getRepository()->findOneByCustomerGroup(new CustomerGroup()));
    }

    public function testFindManyByEntitiesWhenEmptyGroupsGiven()
    {
        $this->assertEmpty($this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_ACCOUNT_GROUP, []));
    }

    public function testFindManyByEntitiesWhenGroupsGiven()
    {
        $expectedTaxCodes = [
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_2),
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_4)
        ];

        $groups = [
            $this->getReference(LoadGroups::GROUP2),
            $this->getReference(LoadGroups::GROUP3)
        ];

        $this->assertEquals(
            $expectedTaxCodes,
            $this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $groups)
        );
    }

    public function testFindManyByEntitiesWhenNewGroupsGiven()
    {
        $expectedTaxCodes = [
            null,
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_2),
            null,
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_4)
        ];

        $groups = [
            new CustomerGroup(),
            $this->getReference(LoadGroups::GROUP2),
            new CustomerGroup(),
            $this->getReference(LoadGroups::GROUP3)
        ];

        $this->assertEquals(
            $expectedTaxCodes,
            $this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $groups)
        );
    }

    public function testFindManyByEntitiesWhenEmptyCustomersGiven()
    {
        $this->assertEmpty($this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_ACCOUNT, []));
    }

    public function testFindManyByEntitiesWhenCustomersGiven()
    {
        $expectedTaxCodes = [
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1),
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_3)
        ];

        $customers = [
            $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME),
            $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1)
        ];

        $this->assertEquals(
            $expectedTaxCodes,
            $this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_ACCOUNT, $customers)
        );
    }

    public function testFindManyByEntitiesWhenNewCustomersGiven()
    {
        $expectedTaxCodes = [
            null,
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1),
            null,
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_3)
        ];

        $customers = [
            new Customer(),
            $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME),
            new Customer(),
            $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1)
        ];

        $this->assertEquals(
            $expectedTaxCodes,
            $this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_ACCOUNT, $customers)
        );
    }

    /**
     * @return CustomerTaxCodeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_tax.repository.customer_tax_code');
    }
}

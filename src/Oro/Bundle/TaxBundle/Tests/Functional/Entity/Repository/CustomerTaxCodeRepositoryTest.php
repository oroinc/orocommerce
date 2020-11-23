<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodesWithAdditionalOrganization as TaxFixture;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerTaxCodeRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([TaxFixture::class]);
    }

    public function testFindByCodes()
    {
        /** @var CustomerTaxCode $taxCode1 */
        $taxCode1 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);

        /** @var CustomerTaxCode $taxCode2 */
        $taxCode2 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_2);

        /** @var CustomerTaxCode $taxCode3 */
        $taxCode3 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_3);

        $result = $this->getRepository()->findByCodes([TaxFixture::TAX_1, TaxFixture::TAX_2, TaxFixture::TAX_3]);

        $this->assertCount(3, $result);
        $this->assertContains($taxCode1, $result);
        $this->assertContains($taxCode2, $result);
        $this->assertContains($taxCode3, $result);
    }

    public function testFindByCodesAndOrganization()
    {
        $organizationAcme = $this->getReference('acme_organization');

        /** @var CustomerTaxCode $taxCode3 */
        $taxCode3 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_3);

        $this->assertEquals([
            $taxCode3,
        ], $this->getRepository()->findByCodes([
            TaxFixture::TAX_1,
            TaxFixture::TAX_2,
            TaxFixture::TAX_3,
        ], $organizationAcme));
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

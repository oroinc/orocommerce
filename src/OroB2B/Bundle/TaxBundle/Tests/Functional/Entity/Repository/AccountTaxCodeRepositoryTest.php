<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes as TaxFixture;

/**
 * @dbIsolation
 */
class AccountTaxCodeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes']);
    }

    public function testFindOneByAccount()
    {
        /** @var Account $account1 */
        $account1 = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $expectedTaxCode = $this->getRepository()->findOneByAccount($account1);

        /** @var AccountTaxCode $taxCode1 */
        $taxCode1 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);
        $this->assertEquals($expectedTaxCode->getId(), $taxCode1->getId());
    }

    public function testFindNewAccount()
    {
        $this->assertEmpty($this->getRepository()->findOneByAccount(new Account()));
    }

    public function testFindByCodes()
    {
        /** @var AccountTaxCode $taxCode1 */
        $taxCode = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);

        $this->assertEquals([$taxCode], $this->getRepository()->findByCodes([TaxFixture::TAX_1]));
    }

    public function testFindOneByAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference(LoadGroups::GROUP2);
        $expectedTaxCode = $this->getRepository()->findOneByAccountGroup($accountGroup);

        /** @var AccountTaxCode $taxCode */
        $taxCode = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_2);
        $this->assertEquals($expectedTaxCode->getId(), $taxCode->getId());
    }

    public function testFindNewAccountGroup()
    {
        $this->assertEmpty($this->getRepository()->findOneByAccountGroup(new AccountGroup()));
    }

    /**
     * @return AccountTaxCodeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_tax.entity.account_tax_code.class')
        );
    }
}

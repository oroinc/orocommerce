<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;

class LoadAccountTaxCodes extends AbstractFixture implements DependentFixtureInterface
{
    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';

    const DESCRIPTION_1 = 'Tax description 1';
    const DESCRIPTION_2 = 'Tax description 2';

    const REFERENCE_PREFIX = 'account_tax_code';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createAccountTaxCode(
            $manager,
            self::TAX_1,
            self::DESCRIPTION_1,
            [LoadAccounts::DEFAULT_ACCOUNT_NAME],
            []
        );
        $this->createAccountTaxCode($manager, self::TAX_2, self::DESCRIPTION_2, [], [LoadGroups::GROUP2]);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param string $description
     * @param array $accountRefs
     * @param array $accountGroupsRefs
     * @return AccountTaxCode
     */
    protected function createAccountTaxCode(
        ObjectManager $manager,
        $code,
        $description,
        array $accountRefs,
        array $accountGroupsRefs
    ) {
        $accountTaxCode = new AccountTaxCode();
        $accountTaxCode->setCode($code);
        $accountTaxCode->setDescription($description);
        foreach ($accountRefs as $accountRef) {
            /** @var Account $account */
            $account = $this->getReference($accountRef);
            $accountTaxCode->addAccount($account);
        }

        foreach ($accountGroupsRefs as $accountGroupRef) {
            /** @var AccountGroup $account */
            $account = $this->getReference($accountGroupRef);
            $accountTaxCode->addAccountGroup($account);
        }

        $manager->persist($accountTaxCode);
        $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $accountTaxCode);

        return $accountTaxCode;
    }
}

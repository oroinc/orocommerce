<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;

class LoadAccountTaxCodes extends AbstractFixture
{
    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createAccountTaxCode($manager, 'TAX1', 'Tax description 1', ['AccountUser AccountUser']);
        $this->createAccountTaxCode($manager, 'TAX2', 'Tax description 2', []);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param string $description
     * @param array $accountRefs
     * @return AccountTaxCode
     */
    protected function createAccountTaxCode(ObjectManager $manager, $code, $description, $accountRefs)
    {
        $accountTaxCode = new AccountTaxCode();
        $accountTaxCode->setCode($code);
        $accountTaxCode->setDescription($description);
        foreach ($accountRefs as $accountRef) {
            $account = $manager->getRepository('OroB2B\Bundle\AccountBundle\Entity\Account')
                ->findOneByName($accountRef);
            $accountTaxCode->addAccount($account);
        }

        $manager->persist($accountTaxCode);
        $this->addReference('account_tax_code.' . $code, $accountTaxCode);

        return $accountTaxCode;
    }
}

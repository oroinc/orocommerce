<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;

class LoadTaxJurisdictions extends AbstractFixture implements DependentFixtureInterface
{
    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';

    const DESCRIPTION_1 = 'Tax description 1';
    const DESCRIPTION_2 = 'Tax description 2';

    const COUNTRY = 'US';
    const STATE = 'US-NY';

    const REFERENCE_PREFIX = 'tax_jurisdiction';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTaxJurisdiction($manager, self::TAX_1, self::DESCRIPTION_1);
        $this->createTaxJurisdiction($manager, self::TAX_2, self::DESCRIPTION_2);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'
        ];
    }

    /**
     * @param ObjectManager $manager
     * @param string        $code
     * @param string        $description
     * @return TaxJurisdiction
     */
    protected function createTaxJurisdiction(ObjectManager $manager, $code, $description)
    {
        $taxJurisdiction = new TaxJurisdiction();
        $taxJurisdiction->setCode($code);
        $taxJurisdiction->setDescription($description);
        $taxJurisdiction->setCountry($manager->getReference('OroAddressBundle:Country', self::COUNTRY));
        $taxJurisdiction->setRegion($manager->getReference('OroAddressBundle:Region', self::STATE));

        $manager->persist($taxJurisdiction);
        $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $taxJurisdiction);

        return $taxJurisdiction;
    }
}

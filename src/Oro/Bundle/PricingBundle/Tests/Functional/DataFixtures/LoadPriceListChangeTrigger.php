<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadPriceListChangeTrigger extends AbstractFixture implements DependentFixtureInterface
{

    /**
     * @var array
     */
    protected $data = [
        [
            'reference' => 'all_scopes_force',
            'account' => null,
            'accountGroup' => null,
            'website' => null,
            'force' => true
        ],
        [
            'reference' => 'pl_changed_config',
            'account' => null,
            'accountGroup' => null,
            'website' => null,
            'force' => false
        ],
        [
            'reference' => 'pl_changed_w1',
            'account' => null,
            'accountGroup' => null,
            'website' => LoadWebsiteData::WEBSITE1,
            'force' => false
        ],
        [
            'reference' => 'pl_changed_w1_g1',
            'account' => null,
            'accountGroup' => 'account_group.group1',
            'website' => LoadWebsiteData::WEBSITE1,
            'force' => false
        ],
        [
            'reference' => 'pl_changed_w1_a1',
            'account' => 'account.level_1',
            'accountGroup' => null,
            'website' => LoadWebsiteData::WEBSITE1,
            'force' => false
        ],
        [
            'reference' => 'pl_changed_w2_a2',
            'account' => 'account.level_1.2',
            'accountGroup' => null,
            'website' => LoadWebsiteData::WEBSITE2,
            'force' => false
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $changes) {
            $changesObject = new PriceListChangeTrigger();
            if ($changes['account']) {
                $changesObject->setAccount($this->getReference($changes['account']));
            }
            if ($changes['accountGroup']) {
                $changesObject->setAccountGroup($this->getReference($changes['accountGroup']));
            }
            if ($changes['website']) {
                $changesObject->setWebsite($this->getReference($changes['website']));
            }

            $changesObject->setForce($changes['force']);

            $manager->persist($changesObject);
            $this->setReference($changes['reference'], $changesObject);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
        ];
    }
}

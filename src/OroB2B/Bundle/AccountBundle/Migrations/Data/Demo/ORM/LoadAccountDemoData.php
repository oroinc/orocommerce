<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class LoadAccountDemoData extends AbstractFixture implements DependentFixtureInterface
{
    const ACCOUNT_REFERENCE_PREFIX = 'account_demo_data';

    /** @var array */
    protected $accounts = [
        'Company A' => [
            'group' => 'All Customers',
            'subsidiaries' => [
                'Company A - East Division' => [
                    'group' => 'All Customers',
                ],
                'Company A - West Division' => [
                    'group' => 'All Customers',
                ],
            ],
        ],
        'Wholesaler B' => [
            'group' => 'Wholesale Accounts'
        ],
        'Partner C' => [
            'group' => 'Partners'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\LoadAccountInternalRatingDemoData',
            __NAMESPACE__ . '\LoadAccountGroupDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $internalRatings = $manager->getRepository(ExtendHelper::buildEnumValueClassName(Account::INTERNAL_RATING_CODE))
            ->findAll();

        /** @var \Oro\Bundle\UserBundle\Entity\User $accountOwner */
        $accountOwner = $manager->getRepository('OroUserBundle:User')->findOneBy([]);

        foreach ($this->accounts as $accountName => $accountData) {
            /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountGroup $accountGroup */
            $accountGroup = $this->getReference(
                LoadAccountGroupDemoData::ACCOUNT_GROUP_REFERENCE_PREFIX . $accountData['group']
            );

            $account = new Account();
            $account
                ->setName($accountName)
                ->setGroup($accountGroup)
                ->setParent(null)
                ->setOrganization($accountOwner->getOrganization())
                ->setOwner($accountOwner)
                ->setInternalRating($internalRatings[array_rand($internalRatings)]);

            $manager->persist($account);
            $this->addReference(static::ACCOUNT_REFERENCE_PREFIX . $account->getName(), $account);

            if (isset($accountData['subsidiaries'])) {
                foreach ($accountData['subsidiaries'] as $subsidiaryName => $subsidiaryData) {
                    /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountGroup $subsidiaryGroup */
                    $subsidiaryGroup = $this->getReference(
                        LoadAccountGroupDemoData::ACCOUNT_GROUP_REFERENCE_PREFIX . $subsidiaryData['group']
                    );
                    $subsidiary = new Account();
                    $subsidiary
                        ->setName($subsidiaryName)
                        ->setGroup($subsidiaryGroup)
                        ->setParent($account)
                        ->setOrganization($accountOwner->getOrganization())
                        ->setOwner($accountOwner)
                        ->setInternalRating($internalRatings[array_rand($internalRatings)]);

                    $manager->persist($subsidiary);
                    $this->addReference(static::ACCOUNT_REFERENCE_PREFIX . $subsidiary->getName(), $subsidiary);
                }
            }
        }

        $manager->flush();
    }
}

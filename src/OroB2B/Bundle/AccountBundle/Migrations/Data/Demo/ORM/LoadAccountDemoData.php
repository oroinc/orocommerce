<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class LoadAccountDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountInternalRatingDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var AccountUser[] $accountUsers */
        $accountUsers = $manager->getRepository('OroB2BAccountBundle:AccountUser')->findAll();
        $internalRatings =
            $manager->getRepository(ExtendHelper::buildEnumValueClassName(Account::INTERNAL_RATING_CODE))->findAll();

        $rootAccount = null;
        $firstLevelAccount = null;

        // create account groups
        $rootGroup = $this->createAccountGroup('Root');
        $firstLevelGroup = $this->createAccountGroup('First');
        $secondLevelGroup = $this->createAccountGroup('Second');

        $manager->persist($rootGroup);
        $manager->persist($firstLevelGroup);
        $manager->persist($secondLevelGroup);

        // create accounts
        foreach ($accountUsers as $index => $accountUser) {
            $account = $accountUser->getAccount();
            switch ($index % 3) {
                case 0:
                    $account->setGroup($rootGroup);
                    $rootAccount = $account;
                    break;
                case 1:
                    $account->setGroup($firstLevelGroup)
                        ->setParent($rootAccount);
                    $firstLevelAccount = $account;
                    break;
                case 2:
                    $account->setGroup($secondLevelGroup)
                        ->setParent($firstLevelAccount);
                    break;
            }
            $account->setInternalRating($internalRatings[array_rand($internalRatings)]);
        }

        $manager->flush();
    }

    /**
     * @param string $name
     * @return AccountGroup
     */
    protected function createAccountGroup($name)
    {
        $accountGroup = new AccountGroup();
        $accountGroup->setName($name);

        return $accountGroup;
    }
}

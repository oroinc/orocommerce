<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;

class LoadProductVisibilityResolvedData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $productVisibilities = [
        'product.1.visibility.all',
        'product.2.visibility.all',
        'product.3.visibility.all',
        'product.4.visibility.all',
        'product.5.visibility.all',
        'product.6.visibility.all',
        'product.7.visibility.all',
    ];

    /**
     * @var array
     */
    protected $accountGroupProductVisibilities = [
        'product.1.visibility.account_group.group1',
        'product.2.visibility.account_group.group1',
        'product.3.visibility.account_group.group1',
        'product.5.visibility.account_group.group1',
    ];

    /**
     * @var array
     */
    protected $accountProductVisibilities = [
        'product.1.visibility.account.level_1',
        'product.2.visibility.account.level_1',
        'product.5.visibility.account.level_1',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->productVisibilities as $productVisibilityReference) {
            /** @var ProductVisibility $productVisibility */
            $productVisibility = $this->getReference($productVisibilityReference);
            $this->createProductVisibilityResolved($manager, $productVisibility);
        }

        foreach ($this->accountGroupProductVisibilities as $accountGroupProductVisibilityReference) {
            /** @var AccountGroupProductVisibility $accountGroupProductVisibility */
            $accountGroupProductVisibility = $this->getReference($accountGroupProductVisibilityReference);
            $this->createAccountGroupProductVisibilityResolved($manager, $accountGroupProductVisibility);
        }

        foreach ($this->accountProductVisibilities as $accountProductVisibilityReference) {
            /** @var AccountProductVisibility $accountProductVisibility */
            $accountProductVisibility = $this->getReference($accountProductVisibilityReference);
            $this->createAccountProductVisibilityResolved($manager, $accountProductVisibility);
        }

        $manager->flush();
    }


    /**
     * @param ObjectManager $manager
     * @param ProductVisibility $productVisibility
     */
    protected function createProductVisibilityResolved(ObjectManager $manager, ProductVisibility $productVisibility)
    {
        $aliases = [
            ProductVisibility::HIDDEN => ProductVisibilityResolved::VISIBILITY_HIDDEN,
            ProductVisibility::VISIBLE => ProductVisibilityResolved::VISIBILITY_VISIBLE,
        ];

        if (isset($aliases[$productVisibility->getVisibility()])) {
            $productVisibilityResolved = (new ProductVisibilityResolved(
                $productVisibility->getWebsite(),
                $productVisibility->getProduct()
            ))
                ->setSourceProductVisibility($productVisibility)
                ->setSource(ProductVisibilityResolved::SOURCE_STATIC)
                ->setVisibility($aliases[$productVisibility->getVisibility()])
            ;
            $manager->persist($productVisibilityResolved);
        }
    }


    /**
     * @param ObjectManager $manager
     * @param AccountGroupProductVisibility $accountGroupProductVisibility
     */
    protected function createAccountGroupProductVisibilityResolved(
        ObjectManager $manager,
        AccountGroupProductVisibility $accountGroupProductVisibility
    ) {
        $aliases = [
            AccountGroupProductVisibility::HIDDEN => AccountGroupProductVisibilityResolved::VISIBILITY_HIDDEN,
            AccountGroupProductVisibility::VISIBLE => AccountGroupProductVisibilityResolved::VISIBILITY_VISIBLE,
        ];

        if (isset($aliases[$accountGroupProductVisibility->getVisibility()])) {
            $accountGroupProductVisibilityResolved = (new AccountGroupProductVisibilityResolved(
                $accountGroupProductVisibility->getWebsite(),
                $accountGroupProductVisibility->getProduct(),
                $accountGroupProductVisibility->getAccountGroup()
            ))
                ->setSourceProductVisibility($accountGroupProductVisibility)
                ->setSource(AccountGroupProductVisibilityResolved::SOURCE_STATIC)
                ->setVisibility($aliases[$accountGroupProductVisibility->getVisibility()])
            ;

            $manager->persist($accountGroupProductVisibilityResolved);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param AccountProductVisibility $accountProductVisibility
     */
    protected function createAccountProductVisibilityResolved(
        ObjectManager $manager,
        AccountProductVisibility $accountProductVisibility
    ) {
        $aliases = [
            AccountProductVisibility::HIDDEN => AccountProductVisibilityResolved::VISIBILITY_HIDDEN,
            AccountProductVisibility::VISIBLE => AccountProductVisibilityResolved::VISIBILITY_VISIBLE,
            AccountProductVisibility::CURRENT_PRODUCT => AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
        ];

        if (isset($aliases[$accountProductVisibility->getVisibility()])) {
            $accountProductVisibilityResolved = (new AccountProductVisibilityResolved(
                $accountProductVisibility->getWebsite(),
                $accountProductVisibility->getProduct(),
                $accountProductVisibility->getAccount()
            ))
                ->setSourceProductVisibility($accountProductVisibility)
                ->setSource(AccountProductVisibilityResolved::SOURCE_STATIC)
                ->setVisibility($aliases[$accountProductVisibility->getVisibility()])
            ;

            $manager->persist($accountProductVisibilityResolved);
        }
    }
}

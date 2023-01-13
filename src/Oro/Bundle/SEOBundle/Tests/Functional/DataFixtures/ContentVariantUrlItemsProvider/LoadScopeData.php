<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\ContentVariantUrlItemsProvider;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LoadScopeData extends AbstractFixture implements DependentFixtureInterface
{
    public const SCOPE_DEFAULT = 'scope_default';
    public const SCOPE_LOCALIZATION_EN_CA = 'scope_localization_en_ca';
    public const SCOPE_CUSTOMER_GROUP_ANONYMOUS = 'scope_customer_group_anonymous';
    public const SCOPE_CUSTOMER_GROUP1 = 'scope_customer_group_1';
    public const SCOPE_CUSTOMER1 = 'scope_customer1';

    protected array $scopesData = [
        self::SCOPE_DEFAULT => [],
        self::SCOPE_LOCALIZATION_EN_CA => ['localization' => LoadLocalizationData::EN_CA_LOCALIZATION_CODE],
        self::SCOPE_CUSTOMER_GROUP_ANONYMOUS => ['customerGroup' => LoadGroups::ANONYMOUS_GROUP],
        self::SCOPE_CUSTOMER_GROUP1 => ['customerGroup' => LoadGroups::GROUP1],
        self::SCOPE_CUSTOMER1 => ['customer' => LoadCustomers::CUSTOMER_LEVEL_1],
    ];

    public function getDependencies()
    {
        return [LoadCustomers::class, LoadLocalizationData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->getRepository(Scope::class)
            ->createQueryBuilder('s')
            ->delete()
            ->getQuery()
            ->execute();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->scopesData as $scopeAlias => $scopeData) {
            $scope = new Scope();
            foreach ($scopeData as $scopeParameter => $scopeRef) {
                $propertyAccessor->setValue(
                    $scope,
                    $scopeParameter,
                    $this->getReference($scopeRef)
                );
            }

            $manager->persist($scope);

            $this->setReference($scopeAlias, $scope);
        }

        $manager->flush();
    }
}

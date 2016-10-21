<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolation
 */
class DefaultVisibilityListenerTest extends WebTestCase
{
    use EntityTrait;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var Account
     */
    protected $account;

    /**
     * @var AccountGroup
     */
    protected $accountGroup;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadProductData::class,
            LoadCategoryData::class,
            LoadGroups::class,
            LoadAccounts::class
        ]);

        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $this->account = $this->getReference('account.level_1');
        $this->accountGroup = $this->getReference(LoadGroups::GROUP1);
    }

    /**
     * @param string $entityClass
     * @param array $parameters
     * @dataProvider onFlushDataProvider
     */
    public function testOnFlushVisibility($entityClass, array $parameters)
    {
        $entityManager = $this->getManager($entityClass);

        $properties = $this->getProperties($parameters);

        // persisted with custom visibility
        /** @var VisibilityInterface $entity */
        $entity = $this->findOneBy($entityClass, $properties);
        if (!$entity) {
            $entity = $this->getEntity($entityClass, $properties);
        }
        $entity->setVisibility(VisibilityInterface::VISIBLE);
        $entityManager->persist($entity);
        $entityManager->flush();
        $this->assertEntitiesSame($entity, $this->findOneBy($entityClass, $properties));
        $this->assertEquals(VisibilityInterface::VISIBLE, $entity->getVisibility());

        // updated with custom visibility
        $entity->setVisibility(VisibilityInterface::HIDDEN);
        $entityManager->flush();
        $this->assertEntitiesSame($entity, $this->findOneBy($entityClass, $properties));
        $this->assertEquals(VisibilityInterface::HIDDEN, $entity->getVisibility());

        // updated with default visibility
        $entity->setVisibility($entity::getDefault($entity->getTargetEntity()));
        $entityManager->flush();
        $this->assertNull($this->findOneBy($entityClass, $properties));

        $entityManager->clear();

        $properties = $this->getProperties($parameters);

        // persisted with default visibility
        $entity = $this->getEntity($entityClass, $properties);
        $entity->setVisibility($entity::getDefault($entity->getTargetEntity()));
        $entityManager->persist($entity);
        $entityManager->flush();
        $this->assertNull($this->findOneBy($entityClass, $properties));
    }

    /**
     * @return array
     */
    public function onFlushDataProvider()
    {
        return [
            'category visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility',
                'parameters' => [
                    'category' => 'category',
                    'scope' => ['category_visibility', []]
                ],
            ],
            'account category visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility',
                'parameters' => [
                    'category' => 'category',
                    'scope' => ['account_category_visibility', ['account']]
                ],
            ],
            'account group category visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility',
                'parameters' => [
                    'category' => 'category',
                    'scope' => ['account_group_category_visibility', ['accountGroup']]
                ],
            ],
            'product visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility',
                'parameters' => [
                    'product' => 'product',
                    'scope' => [ProductVisibility::VISIBILITY_TYPE, []]
                ],
            ],
            'account product visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility',
                'parameters' => [
                    'product' => 'product',
                    'scope' => ['account_product_visibility', ['account']]
                ],
            ],
            'account group product visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility',
                'parameters' => [
                    'product' => 'product',
                    'scope' => [AccountGroupProductVisibility::VISIBILITY_TYPE, ['accountGroup']]
                ],

            ],
        ];
    }

    /**
     * @param array $parameters
     * @return array
     */
    protected function getProperties(array $parameters)
    {
        $registry = $this->getContainer()->get('doctrine');
        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $properties = [];
        foreach ($parameters as $key => $parameter) {
            if ($key == 'scope') {
                $scope = $scopeManager->findOrCreate($parameter[0], $parameter[1]);
                $properties[$key] = $scope;
            } else {
                $fixtureValue = $this->$parameter;
                $entityClass = ClassUtils::getClass($fixtureValue);
                $entityManager = $registry->getManagerForClass($entityClass);
                $identifier = $entityManager->getClassMetadata($entityClass)->getIdentifierValues($fixtureValue);
                $properties[$key] = $entityManager->getRepository($entityClass)->find($identifier);
            }
        }

        return $properties;
    }

    /**
     * @param string $entityClass
     * @return ObjectManager
     */
    protected function getManager($entityClass)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }

    /**
     * @param string $entityClass
     * @param array $criteria
     * @return object|null
     */
    protected function findOneBy($entityClass, array $criteria)
    {
        return $this->getManager($entityClass)->getRepository($entityClass)->findOneBy($criteria);
    }

    /**
     * @param object $expected
     * @param object $actual
     */
    protected function assertEntitiesSame($expected, $actual)
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $this->assertEquals(
            $propertyAccessor->getValue($expected, 'id'),
            $propertyAccessor->getValue($actual, 'id')
        );
    }
}

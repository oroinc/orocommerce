<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Component\Testing\Unit\EntityTrait;

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
     * @var Customer
     */
    protected $customer;

    /**
     * @var CustomerGroup
     */
    protected $customerGroup;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadProductData::class,
            LoadCategoryData::class,
            LoadGroups::class,
            LoadCustomers::class
        ]);

        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $this->customer = $this->getReference('customer.level_1');
        $this->customerGroup = $this->getReference(LoadGroups::GROUP1);
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
            'customer category visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility',
                'parameters' => [
                    'category' => 'category',
                    'scope' => ['customer_category_visibility', ['customer']]
                ],
            ],
            'customer group category visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility',
                'parameters' => [
                    'category' => 'category',
                    'scope' => ['customer_group_category_visibility', ['customerGroup']]
                ],
            ],
            'product visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility',
                'parameters' => [
                    'product' => 'product',
                    'scope' => [ProductVisibility::VISIBILITY_TYPE, []]
                ],
            ],
            'customer product visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility',
                'parameters' => [
                    'product' => 'product',
                    'scope' => ['customer_product_visibility', ['customer']]
                ],
            ],
            'customer group product visibility' => [
                'entityClass' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility',
                'parameters' => [
                    'product' => 'product',
                    'scope' => [CustomerGroupProductVisibility::VISIBILITY_TYPE, ['customerGroup']]
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

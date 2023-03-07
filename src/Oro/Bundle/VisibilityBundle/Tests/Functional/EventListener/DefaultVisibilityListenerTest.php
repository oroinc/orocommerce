<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\EntityExtendBundle\Decorator\OroPropertyAccessorBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DefaultVisibilityListenerTest extends WebTestCase
{
    private Product $product;
    private Category $category;
    private Customer $customer;
    private CustomerGroup $customerGroup;

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
     * @dataProvider onFlushDataProvider
     */
    public function testOnFlushVisibility(string $entityClass, array $parameters)
    {
        $entityManager = $this->getManager($entityClass);

        $properties = $this->getProperties($parameters);

        // persisted with custom visibility
        /** @var VisibilityInterface $entity */
        $entity = $this->findOneBy($entityClass, $properties);
        if (!$entity) {
            $entity = $this->createEntity($entityClass, $properties);
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
        $entity = $this->createEntity($entityClass, $properties);
        $entity->setVisibility($entity::getDefault($entity->getTargetEntity()));
        $entityManager->persist($entity);
        $entityManager->flush();
        $this->assertNull($this->findOneBy($entityClass, $properties));
    }

    public function onFlushDataProvider(): array
    {
        return [
            'category visibility' => [
                'entityClass' => CategoryVisibility::class,
                'parameters' => [
                    'category' => 'category',
                    'scope' => ['category_visibility', []]
                ],
            ],
            'customer category visibility' => [
                'entityClass' => CustomerCategoryVisibility::class,
                'parameters' => [
                    'category' => 'category',
                    'scope' => ['customer_category_visibility', ['customer']]
                ],
            ],
            'customer group category visibility' => [
                'entityClass' => CustomerGroupCategoryVisibility::class,
                'parameters' => [
                    'category' => 'category',
                    'scope' => ['customer_group_category_visibility', ['customerGroup']]
                ],
            ],
            'product visibility' => [
                'entityClass' => ProductVisibility::class,
                'parameters' => [
                    'product' => 'product',
                    'scope' => [ProductVisibility::VISIBILITY_TYPE, []]
                ],
            ],
            'customer product visibility' => [
                'entityClass' => CustomerProductVisibility::class,
                'parameters' => [
                    'product' => 'product',
                    'scope' => ['customer_product_visibility', ['customer']]
                ],
            ],
            'customer group product visibility' => [
                'entityClass' => CustomerGroupProductVisibility::class,
                'parameters' => [
                    'product' => 'product',
                    'scope' => [CustomerGroupProductVisibility::VISIBILITY_TYPE, ['customerGroup']]
                ],

            ],
        ];
    }

    private function getProperties(array $parameters): array
    {
        $registry = $this->getContainer()->get('doctrine');
        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $properties = [];
        foreach ($parameters as $key => $parameter) {
            if ($key === 'scope') {
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

    private function getManager(string $entityClass): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }

    private function findOneBy(string $entityClass, array $criteria): ?object
    {
        return $this->getManager($entityClass)->getRepository($entityClass)->findOneBy($criteria);
    }

    private function createEntity(string $entityClass, array $properties): object
    {
        $entity = new $entityClass();
        $propertyAccessor = $this->getPropertyAccessor();
        foreach ($properties as $name => $val) {
            $propertyAccessor->setValue($entity, $name, $val);
        }

        return $entity;
    }

    private function assertEntitiesSame(object $expected, object $actual): void
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $this->assertEquals(
            $propertyAccessor->getValue($expected, 'id'),
            $propertyAccessor->getValue($actual, 'id')
        );
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        return (new OroPropertyAccessorBuilder())->getPropertyAccessor();
    }
}

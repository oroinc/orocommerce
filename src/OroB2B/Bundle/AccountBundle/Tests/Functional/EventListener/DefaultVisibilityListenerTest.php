<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class DefaultVisibilityListenerTest extends WebTestCase
{
    use EntityTrait;

    /**
     * @var Website
     */
    protected $website;

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
        $this->loadFixtures([
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
        ]);

        $this->website = $this->getReference(LoadWebsiteData::WEBSITE1);
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
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility',
                'parameters' => ['category'],
            ],
            'account category visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility',
                'parameters' => ['category', 'account'],
            ],
            'account group category visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility',
                'parameters' => ['category', 'accountGroup'],
            ],
            'product visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility',
                'parameters' => ['website', 'product'],
            ],
            'account product visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility',
                'parameters' => ['website', 'product', 'account'],
            ],
            'account group product visibility' => [
                'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility',
                'parameters' => ['website', 'product', 'accountGroup'],
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

        $properties = [];
        foreach ($parameters as $parameter) {
            $fixtureValue = $this->$parameter;
            $entityClass = ClassUtils::getClass($fixtureValue);
            $entityManager = $registry->getManagerForClass($entityClass);
            $identifier = $entityManager->getClassMetadata($entityClass)->getIdentifierValues($fixtureValue);
            $properties[$parameter] = $entityManager->getRepository($entityClass)->find($identifier);
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

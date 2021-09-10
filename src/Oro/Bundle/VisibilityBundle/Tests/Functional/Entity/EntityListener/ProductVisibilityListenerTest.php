<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductVisibilityListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var Product */
    private $product;

    /** @var Registry */
    private $registry;

    /** @var CustomerGroup */
    private $customerGroup;

    /** @var Customer */
    private $customer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductVisibilityData::class]);
        self::enableMessageBuffering();

        $this->getOptionalListenerManager()->enableListener('oro_visibility.entity_listener.product_visibility_change');

        $this->registry = $this->client->getContainer()->get('doctrine');
        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->customerGroup = $this->getReference(LoadGroups::GROUP1);
        $this->customer = $this->getReference('customer.level_1');
    }

    private function getScopeManager(): ScopeManager
    {
        return self::getContainer()->get('oro_scope.scope_manager');
    }

    private function getManagerForProductVisibility(): EntityManagerInterface
    {
        return $this->registry->getManagerForClass(ProductVisibility::class);
    }

    private function getManagerForCustomerGroupProductVisibility(): EntityManagerInterface
    {
        return $this->registry->getManagerForClass(CustomerGroupProductVisibility::class);
    }

    private function getManagerForCustomerProductVisibility(): EntityManagerInterface
    {
        return $this->registry->getManagerForClass(CustomerProductVisibility::class);
    }

    public function testChangeProductVisibilityToHidden()
    {
        $scope = $this->getScopeManager()->findOrCreate(ProductVisibility::VISIBILITY_TYPE);
        $entityManager = $this->getManagerForProductVisibility();
        $visibility = $entityManager->getRepository(ProductVisibility::class)->findOneBy(
            ['product' => $this->product, 'scope' => $scope]
        );
        $entityManager->remove($visibility);
        $entityManager->flush();

        self::clearMessageCollector();

        // Create new product visibility entity
        $visibility = new ProductVisibility();
        $visibility->setScope($scope);
        $visibility->setProduct($this->product);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => ProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeProductVisibilityToVisible()
    {
        // Already exists product visibility with `HIDDEN` value
        $visibility = $this->getReference('product-4.visibility.all');

        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => ProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeProductVisibilityToConfig()
    {
        // Already exists product visibility with `VISIBLE` value
        $visibility = $this->getReference('product-2.visibility.all');

        $visibility->setVisibility(ProductVisibility::CONFIG);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => ProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeProductVisibilityToCategory()
    {
        $scope = $this->getScopeManager()->findOrCreate('product_visiblity');
        $entityManager = $this->getManagerForProductVisibility();
        $visibility = $entityManager->getRepository(ProductVisibility::class)->findOneBy(
            ['scope' => $scope, 'product' => $this->product]
        );

        $visibility->setVisibility(ProductVisibility::CATEGORY);
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                // no "id" because default value will be deleted
                'entity_class_name' => ProductVisibility::class,
                'target_class_name' => Product::class,
                'target_id'         => $visibility->getProduct()->getId(),
                'scope_id'          => $visibility->getScope()->getId()
            ]
        );
    }

    public function testChangeCustomerGroupProductVisibilityToHidden()
    {
        $scope = $this->getScopeManager()->findOrCreate(
            CustomerGroupProductVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $this->customerGroup]
        );
        // Create new customer group product visibility entity
        $visibility = new CustomerGroupProductVisibility();
        $visibility->setScope($scope);
        $visibility->setProduct($this->getReference(LoadProductData::PRODUCT_4));
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForCustomerGroupProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => CustomerGroupProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeCustomerGroupProductVisibilityToVisible()
    {
        // Already exists customer group product visibility with `HIDDEN` value
        $visibility = $this->getReference('product-1.visibility.customer_group.group1');
        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForCustomerGroupProductVisibility();
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => CustomerGroupProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeCustomerGroupProductVisibilityToCategory()
    {
        // Already exists customer group product visibility with `VISIBLE` value
        $visibility = $this->getReference('product-2.visibility.customer_group.group1');
        $visibility->setVisibility(CustomerGroupProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForCustomerGroupProductVisibility();
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => CustomerGroupProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeCustomerGroupProductVisibilityToCurrentProduct()
    {
        // Already exists customer group product visibility with `CATEGORY` value
        $visibility = $this->getReference('продукт-7.visibility.customer_group.group1');
        $visibility->setVisibility(CustomerGroupProductVisibility::CURRENT_PRODUCT);

        $entityManager = $this->getManagerForCustomerGroupProductVisibility();
        $expectedMessage = [
            // no "id" because default value will be deleted
            'entity_class_name' => CustomerGroupProductVisibility::class,
            'target_class_name' => Product::class,
            'target_id'         => $visibility->getProduct()->getId(),
            'scope_id'          => $visibility->getScope()->getId()
        ];

        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $expectedMessage
        );
    }

    public function testChangeCustomerProductVisibilityToHidden()
    {
        $scope = $this->getScopeManager()->findOrCreate('customer_product_visibility', ['customer' => $this->customer]);
        $entityManager = $this->getManagerForCustomerProductVisibility();
        $visibility = $entityManager->getRepository(CustomerProductVisibility::class)->findOneBy(
            ['product' => $this->product, 'scope' => $scope]
        );
        $entityManager->remove($visibility);
        $entityManager->flush();

        self::clearMessageCollector();

        $visibility = new CustomerProductVisibility();
        $visibility->setScope($scope);
        $visibility->setProduct($this->product);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager->persist($visibility);
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => CustomerProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeCustomerProductVisibilityToVisible()
    {
        // Already exists customer group product visibility with `HIDDEN` value
        $visibility = $this->getReference('product-2.visibility.customer.level_1');
        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForCustomerProductVisibility();
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => CustomerProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeCustomerProductVisibilityToCategory()
    {
        // Already exists customer group product visibility with `VISIBLE` value
        $visibility = $this->getReference('product-5.visibility.customer.level_1');
        $visibility->setVisibility(CustomerProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForCustomerProductVisibility();
        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            [
                'entity_class_name' => CustomerProductVisibility::class,
                'id'                => $visibility->getId()
            ]
        );
    }

    public function testChangeCustomerProductVisibilityToCustomerGroup()
    {
        // Already exists customer group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product-2.visibility.customer.level_1');
        $visibility->setVisibility(CustomerProductVisibility::CUSTOMER_GROUP);

        $entityManager = $this->getManagerForCustomerProductVisibility();

        $expectedMessage = [
            // no "id" because default value will be deleted
            'entity_class_name' => CustomerProductVisibility::class,
            'target_class_name' => Product::class,
            'target_id'         => $visibility->getProduct()->getId(),
            'scope_id'          => $visibility->getScope()->getId()
        ];

        $entityManager->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $expectedMessage
        );
    }
}

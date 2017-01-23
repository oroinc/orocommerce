<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\MessageQueueTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolation
 */
class ProductVisibilityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /** @var  Product */
    protected $product;

    /** @var  Registry */
    protected $registry;

    /** @var  CustomerGroup */
    protected $customerGroup;

    /** @var  Customer */
    protected $customer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductVisibilityData::class,
        ]);

        $this->registry = $this->client->getContainer()->get('doctrine');
        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->customerGroup = $this->getReference(LoadGroups::GROUP1);
        $this->customer = $this->getReference('customer.level_1');

        $this->cleanScheduledMessages();
    }

    /**
     * @return VisibilityMessageHandler
     */
    protected function getMessageHandler()
    {
        return $this->getContainer()->get('oro_visibility.visibility_message_handler');
    }

    /**
     * @return ScopeManager
     */
    protected function getScopeManager()
    {
        return $this->getContainer()->get('oro_scope.scope_manager');
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

        $this->cleanScheduledMessages();

        // Create new product visibility entity
        $visibility = new ProductVisibility();
        $visibility->setScope($scope);
        $visibility->setProduct($this->product);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $this->product->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
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

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),

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

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
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
        $visibilityId = $visibility->getId();


        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibilityId,
                VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
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

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => CustomerGroupProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
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

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => CustomerGroupProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
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

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => CustomerGroupProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
            ]
        );
    }

    public function testChangeCustomerGroupProductVisibilityToCurrentProduct()
    {
        // Already exists customer group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product-7.visibility.customer_group.group1');
        $visibility->setVisibility(CustomerGroupProductVisibility::CURRENT_PRODUCT);

        $entityManager = $this->getManagerForCustomerGroupProductVisibility();
        $expectedMessage = [
            VisibilityMessageFactory::ID => $visibility->getId(),
            VisibilityMessageFactory::ENTITY_CLASS_NAME => CustomerGroupProductVisibility::class,
            VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
            VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
            VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
        ];

        $entityManager->flush();
        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
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
        $this->cleanScheduledMessages();

        $visibility = new CustomerProductVisibility();
        $visibility->setScope($scope);
        $visibility->setProduct($this->product);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager->persist($visibility);
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => CustomerProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
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

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => CustomerProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
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

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => CustomerProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
            ]
        );
    }

    public function testChangeCustomerProductVisibilityToCustomerGroup()
    {
        $this->cleanScheduledMessages();
        // Already exists customer group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product-2.visibility.customer.level_1');
        $visibility->setVisibility(CustomerProductVisibility::ACCOUNT_GROUP);

        $entityManager = $this->getManagerForCustomerProductVisibility();

        $expectedMessage = [
            VisibilityMessageFactory::ID => $visibility->getId(),
            VisibilityMessageFactory::ENTITY_CLASS_NAME => CustomerProductVisibility::class,
            VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
            VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
            VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
        ];

        $entityManager->flush();
        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            $expectedMessage
        );
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForProductVisibility()
    {
        return $this->registry->getManagerForClass(ProductVisibility::class);
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForCustomerGroupProductVisibility()
    {
        return $this->registry->getManagerForClass(CustomerGroupProductVisibility::class);
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForCustomerProductVisibility()
    {
        return $this->registry->getManagerForClass(CustomerProductVisibility::class);
    }
}

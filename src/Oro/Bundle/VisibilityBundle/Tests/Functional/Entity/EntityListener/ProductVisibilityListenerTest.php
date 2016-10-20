<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\AccountBundle\Tests\Functional\MessageQueueTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

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

    /** @var  AccountGroup */
    protected $accountGroup;

    /** @var  Account */
    protected $account;

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
        $this->accountGroup = $this->getReference(LoadGroups::GROUP1);
        $this->account = $this->getReference('account.level_1');

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
        $scope = $this->getScopeManager()->findOrCreate('product_visibility');
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
        $visibility = $this->getReference('product.4.visibility.all');

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
        $visibility = $this->getReference('product.2.visibility.all');

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

    public function testChangeAccountGroupProductVisibilityToHidden()
    {
        $scope = $this->getScopeManager()->findOrCreate(
            'account_group_product_visibility',
            ['accountGroup' => $this->accountGroup]
        );
        // Create new account group product visibility entity
        $visibility = new AccountGroupProductVisibility();
        $visibility->setScope($scope);
        $visibility->setProduct($this->getReference(LoadProductData::PRODUCT_4));
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
            ]
        );
    }

    public function testChangeAccountGroupProductVisibilityToVisible()
    {
        // Already exists account group product visibility with `HIDDEN` value
        $visibility = $this->getReference('product.1.visibility.account_group.group1');
        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
            ]
        );
    }

    public function testChangeAccountGroupProductVisibilityToCategory()
    {
        // Already exists account group product visibility with `VISIBLE` value
        $visibility = $this->getReference('product.2.visibility.account_group.group1');
        $visibility->setVisibility(AccountGroupProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
            ]
        );
    }

    public function testChangeAccountGroupProductVisibilityToCurrentProduct()
    {
        // Already exists account group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product.7.visibility.account_group.group1');
        $visibility->setVisibility(AccountGroupProductVisibility::CURRENT_PRODUCT);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $expectedMessage = [
            VisibilityMessageFactory::ID => $visibility->getId(),
            VisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
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

    public function testChangeAccountProductVisibilityToHidden()
    {
        $scope = $this->getScopeManager()->findOrCreate('account_product_visibility', ['account' => $this->account]);
        $entityManager = $this->getManagerForAccountProductVisibility();
        $visibility = $entityManager->getRepository(AccountProductVisibility::class)->findOneBy(
            ['product' => $this->product, 'scope' => $scope]
        );
        $entityManager->remove($visibility);
        $entityManager->flush();
        $this->cleanScheduledMessages();

        $visibility = new AccountProductVisibility();
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
                VisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
            ]
        );
    }

    public function testChangeAccountProductVisibilityToVisible()
    {
        // Already exists account group product visibility with `HIDDEN` value
        $visibility = $this->getReference('product.2.visibility.account.level_1');
        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
            ]
        );
    }

    public function testChangeAccountProductVisibilityToCategory()
    {
        // Already exists account group product visibility with `VISIBLE` value
        $visibility = $this->getReference('product.5.visibility.account.level_1');
        $visibility->setVisibility(AccountProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_visibility.visibility.resolve_product_visibility',
            [
                VisibilityMessageFactory::ID => $visibility->getId(),
                VisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => $visibility->getProduct()->getId(),
                VisibilityMessageFactory::SCOPE_ID => $visibility->getScope()->getId(),
            ]
        );
    }

    public function testChangeAccountProductVisibilityToAccountGroup()
    {
        $this->cleanScheduledMessages();
        // Already exists account group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product.2.visibility.account.level_1');
        $visibility->setVisibility(AccountProductVisibility::ACCOUNT_GROUP);

        $entityManager = $this->getManagerForAccountProductVisibility();

        $expectedMessage = [
            VisibilityMessageFactory::ID => $visibility->getId(),
            VisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
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
    protected function getManagerForAccountGroupProductVisibility()
    {
        return $this->registry->getManagerForClass(AccountGroupProductVisibility::class);
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForAccountProductVisibility()
    {
        return $this->registry->getManagerForClass(AccountProductVisibility::class);
    }
}

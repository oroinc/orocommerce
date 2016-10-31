<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\CustomerBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\CustomerBundle\Model\ProductVisibilityMessageFactory;
use Oro\Bundle\CustomerBundle\Model\VisibilityMessageHandler;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\CustomerBundle\Tests\Functional\MessageQueueTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolation
 */
class ProductVisibilityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /** @var  Website */
    protected $website;

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
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ]);

        $this->registry = $this->client->getContainer()->get('doctrine');
        $this->website = $this->getReference(LoadWebsiteData::WEBSITE1);
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
        return $this->getContainer()->get('oro_customer.product_visibility_message_handler');
    }

    public function testChangeProductVisibilityToHidden()
    {
        // Create new product visibility entity
        $visibility = new ProductVisibility();
        $visibility->setWebsite($this->website);
        $visibility->setProduct($this->product);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
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
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
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
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
            ]
        );
    }

    public function testChangeProductVisibilityToCategory()
    {
        // Already exists product visibility with `VISIBLE` value
        $visibility = $this->getReference('product.1.visibility.all');

        $visibility->setVisibility(ProductVisibility::CATEGORY);
        $visibilityId = $visibility->getId();

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibilityId,
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
            ]
        );
    }

    public function testChangeAccountGroupProductVisibilityToHidden()
    {
        // Create new account group product visibility entity
        $visibility = new AccountGroupProductVisibility();
        $visibility->setWebsite($this->website);
        $visibility->setProduct($this->product);
        $visibility->setAccountGroup($this->accountGroup);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
                ProductVisibilityMessageFactory::ACCOUNT_GROUP_ID => $visibility->getAccountGroup()->getId(),
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
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
                ProductVisibilityMessageFactory::ACCOUNT_GROUP_ID => $visibility->getAccountGroup()->getId(),
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
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
                ProductVisibilityMessageFactory::ACCOUNT_GROUP_ID => $visibility->getAccountGroup()->getId(),
            ]
        );
    }

    public function testChangeAccountGroupProductVisibilityToCurrentProduct()
    {
        // Already exists account group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product.7.visibility.account_group.group1');
        $visibility->setVisibility(AccountGroupProductVisibility::CURRENT_PRODUCT);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertEmptyMessages('oro_customer.visibility.resolve_product_visibility');
    }


    public function testChangeAccountProductVisibilityToHidden()
    {
        $visibility = new AccountProductVisibility();
        $visibility->setWebsite($this->website);
        $visibility->setProduct($this->product);
        $visibility->setAccount($this->account);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
                ProductVisibilityMessageFactory::ACCOUNT_ID => $visibility->getAccount()->getId(),
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
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
                ProductVisibilityMessageFactory::ACCOUNT_ID => $visibility->getAccount()->getId(),
            ]
        );
    }

    public function testChangeAccountProductVisibilityToCategory()
    {
        // Already exists account group product visibility with `VISIBLE` value
        $visibility = $this->getReference('product.1.visibility.account.level_1');
        $visibility->setVisibility(AccountProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            'oro_customer.visibility.resolve_product_visibility',
            [
                ProductVisibilityMessageFactory::ID => $visibility->getId(),
                ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
                ProductVisibilityMessageFactory::PRODUCT_ID => $visibility->getProduct()->getId(),
                ProductVisibilityMessageFactory::WEBSITE_ID => $visibility->getWebsite()->getId(),
                ProductVisibilityMessageFactory::ACCOUNT_ID => $visibility->getAccount()->getId(),
            ]
        );
    }

    public function testChangeAccountProductVisibilityToAccountGroup()
    {
        // Already exists account group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product.8.visibility.account.level_1');
        $visibility->setVisibility(AccountProductVisibility::ACCOUNT_GROUP);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->flush();

        $this->sendScheduledMessages();

        self::assertEmptyMessages('oro_customer.visibility.resolve_product_visibility');
    }
    
    /**
     * @return EntityManager
     */
    protected function getManagerForProductVisibility()
    {
        return $this->registry->getManagerForClass('OroCustomerBundle:Visibility\ProductVisibility');
    }
    
    /**
     * @return EntityManager
     */
    protected function getManagerForAccountGroupProductVisibility()
    {
        return $this->registry->getManagerForClass('OroCustomerBundle:Visibility\AccountGroupProductVisibility');
    }
    
    /**
     * @return EntityManager
     */
    protected function getManagerForAccountProductVisibility()
    {
        return $this->registry->getManagerForClass('OroCustomerBundle:Visibility\AccountProductVisibility');
    }
}

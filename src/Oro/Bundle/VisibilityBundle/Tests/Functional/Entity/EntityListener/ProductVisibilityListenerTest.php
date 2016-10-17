<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\AccountBundle\Tests\Functional\MessageQueueTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;

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
        $this->topic = 'oro_visibility.visibility.resolve_product_visibility';
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ]);

        $this->registry = $this->client->getContainer()->get('doctrine');
        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->accountGroup = $this->getReference(LoadGroups::GROUP1);
        $this->account = $this->getReference('account.level_1');

        $this->cleanQueueMessageTraces();
    }

    /**
     * @return VisibilityMessageHandler
     */
    protected function getMessageHandler()
    {
        return $this->getContainer()->get('oro_visibility.visibility_message_handler');
    }

    public function testChangeProductVisibilityToHidden()
    {
        // Create new product visibility entity
        $visibility = new ProductVisibility();
        $visibility->setProduct($this->product);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(ProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeProductVisibilityToVisible()
    {
        // Already exists product visibility with `HIDDEN` value
        $visibility = $this->getReference('product.4.visibility.all');

        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(ProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeProductVisibilityToConfig()
    {
        // Already exists product visibility with `VISIBLE` value
        $visibility = $this->getReference('product.2.visibility.all');

        $visibility->setVisibility(ProductVisibility::CONFIG);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(ProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeProductVisibilityToCategory()
    {
        // Already exists product visibility with `VISIBLE` value
        $visibility = $this->getReference('product.1.visibility.all');

        $visibility->setVisibility(ProductVisibility::CATEGORY);
        $visibilityId = $visibility->getId();

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(ProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibilityId, $actual);
    }

    public function testChangeAccountGroupProductVisibilityToHidden()
    {
        // Create new account group product visibility entity
        $visibility = new AccountGroupProductVisibility();
        $visibility->setProduct($this->product);
        $visibility->setAccountGroup($this->accountGroup);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(AccountGroupProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeAccountGroupProductVisibilityToVisible()
    {
        // Already exists account group product visibility with `HIDDEN` value
        $visibility = $this->getReference('product.1.visibility.account_group.group1');
        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(AccountGroupProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeAccountGroupProductVisibilityToCategory()
    {
        // Already exists account group product visibility with `VISIBLE` value
        $visibility = $this->getReference('product.2.visibility.account_group.group1');
        $visibility->setVisibility(AccountGroupProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(AccountGroupProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeAccountGroupProductVisibilityToCurrentProduct()
    {
        // Already exists account group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product.7.visibility.account_group.group1');
        $visibility->setVisibility(AccountGroupProductVisibility::CURRENT_PRODUCT);

        $entityManager = $this->getManagerForAccountGroupProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(AccountGroupProductVisibility::class);
        $this->assertEmpty($actual);
    }


    public function testChangeAccountProductVisibilityToHidden()
    {
        $visibility = new AccountProductVisibility();
        $visibility->setProduct($this->product);
        $visibility->setAccount($this->account);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(AccountProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeAccountProductVisibilityToVisible()
    {
        // Already exists account group product visibility with `HIDDEN` value
        $visibility = $this->getReference('product.2.visibility.account.level_1');
        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(AccountProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeAccountProductVisibilityToCategory()
    {
        // Already exists account group product visibility with `VISIBLE` value
        $visibility = $this->getReference('product.1.visibility.account.level_1');
        $visibility->setVisibility(AccountProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(AccountProductVisibility::class);
        $this->assertCount(1, $actual);
        $this->assertContains($visibility->getId(), $actual);
    }

    public function testChangeAccountProductVisibilityToAccountGroup()
    {
        // Already exists account group product visibility with `CATEGORY` value
        $visibility = $this->getReference('product.8.visibility.account.level_1');
        $visibility->setVisibility(AccountProductVisibility::ACCOUNT_GROUP);

        $entityManager = $this->getManagerForAccountProductVisibility();
        $entityManager->flush();

        $actual = $this->getActualScheduledEntityIds(AccountProductVisibility::class);
        $this->assertEmpty($actual);
    }
    
    /**
     * @return EntityManager
     */
    protected function getManagerForProductVisibility()
    {
        return $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\ProductVisibility');
    }
    
    /**
     * @return EntityManager
     */
    protected function getManagerForAccountGroupProductVisibility()
    {
        return $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupProductVisibility');
    }
    
    /**
     * @return EntityManager
     */
    protected function getManagerForAccountProductVisibility()
    {
        return $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountProductVisibility');
    }

    /**
     * @param string $visibilityClass
     * @return array
     */
    protected function getActualScheduledEntityIds($visibilityClass)
    {
        $result = [];

        foreach ($this->getQueueMessageTraces() as $item) {
            $result[$item['message'][VisibilityMessageFactory::ENTITY_CLASS_NAME]][]
                = $item['message'][VisibilityMessageFactory::ID];
        }

        return isset($result[ClassUtils::getRealClass($visibilityClass)])
            ? $result[ClassUtils::getRealClass($visibilityClass)]
            : [];
    }
}

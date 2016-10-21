<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Model;

use Oro\Bundle\CustomerBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\CustomerBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData as AccountLoadAccountUserData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @dbIsolation
 */
class ProductVisibilityQueryBuilderModifierTest extends WebTestCase
{
    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_customer.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_customer.category_visibility';

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(AccountLoadAccountUserData::EMAIL, AccountLoadAccountUserData::PASSWORD)
        );

        $this->loadFixtures([
            'Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ]);

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->modifier = new ProductVisibilityQueryBuilderModifier(
            $this->configManager,
            $this->tokenStorage,
            $this->websiteManager,
            $this->getContainer()->get('oro_customer.provider.account_user_relations_provider')
        );
        $this->getContainer()->get('oro_customer.visibility.cache.cache_builder')->buildCache();
    }

    /**
     * @dataProvider modifyDataProvider
     *
     * @param string $configValue
     * @param string|null $user
     * @param array $expectedData
     */
    public function testModify($configValue, $user, $expectedData)
    {
        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($this->getDefaultWebsite());

        if ($user) {
            $user = $this->getReference($user);
        }
        $this->setupTokenStorage($user);

        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with(static::PRODUCT_VISIBILITY_CONFIGURATION_PATH)
            ->willReturn($configValue);
        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with(static::CATEGORY_VISIBILITY_CONFIGURATION_PATH)
            ->willReturn($configValue);

        $this->modifier->setProductVisibilitySystemConfigurationPath(static::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->modifier->setCategoryVisibilitySystemConfigurationPath(static::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $this->modifier->modify($queryBuilder);

        $this->assertEquals($expectedData, array_map(function ($productData) {
            return $productData['sku'];
        }, $queryBuilder->getQuery()->execute()));
    }

    /**
     * @return array
     */
    public function modifyDataProvider()
    {
        return [
            'config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => AccountLoadAccountUserData::EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.5',
                    'product.6',
                ]
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => AccountLoadAccountUserData::EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.6',
                ]
            ],
            'anonymous config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => null,
                'expectedData' => [
                    'product.1',
                    'product.2',
                    'product.3',
                    'product.5',
                    'product.6',
                ]
            ],
            'anonymous config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => null,
                'expectedData' => [
                    'product.2',
                    'product.3',
                    'product.5',
                    'product.6',
                ]
            ],
            'group config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => AccountLoadAccountUserData::GROUP2_EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.3',
                    'product.6',
                ]
            ],
            'account without group and config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => AccountLoadAccountUserData::ORPHAN_EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.2',
                    'product.3',
                    'product.4',
                    'product.5',
                    'product.6',
                ]
            ],
            'account without group and config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => AccountLoadAccountUserData::ORPHAN_EMAIL,
                'expectedData' => [
                    'product.2',
                    'product.3',
                    'product.4',
                    'product.6',
                ]
            ],
        ];
    }

    public function testVisibilityProductSystemConfigurationPathNotSet()
    {
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getDefaultWebsite());

        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $message = sprintf('%s::productConfigPath not configured', get_class($this->modifier));
        $this->setExpectedException('\LogicException', $message);
        $this->modifier->modify($queryBuilder);
    }

    public function testVisibilityProductCategoryConfigurationPathNotSet()
    {
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getDefaultWebsite());

        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $message = sprintf('%s::categoryConfigPath not configured', get_class($this->modifier));
        $this->setExpectedException('\LogicException', $message);
        $this->modifier->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->modifier->modify($queryBuilder);
    }

    /**
     * @param object|null $user
     */
    protected function setupTokenStorage($user = null)
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }

    /**
     * @return \Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class);
    }

    protected function getDefaultWebsite()
    {
        return $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();
    }
}

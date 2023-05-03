<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityFallbackCategoryForAnonymousData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityScopedData;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @dbIsolationPerTest
 */
class ProductVisibilityQueryBuilderModifierTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.product_visibility';
    private const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.category_visibility';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductVisibilityQueryBuilderModifier */
    private $modifier;

    /**
     * @dataProvider modifyDataProvider
     */
    public function testModify(string $configValue, ?string $user, array $expectedData)
    {
        $this->setUpForModifyMethodTests();

        if ($user) {
            /** @var CustomerUser $user */
            $user = $this->getReference($user);
            $token = new UsernamePasswordToken($user, $user->getPassword(), 'key');
            $this->client->getContainer()->get('security.token_storage')->setToken($token);
        } else {
            $this->client->getContainer()->get('security.token_storage')->setToken(null);
        }

        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH, false, false, null, $configValue],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH, false, false, null, $configValue]
            ]);

        $this->modifier->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->modifier->setCategoryVisibilitySystemConfigurationPath(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $this->modifier->modify($queryBuilder);

        $this->assertEquals($expectedData, array_map(function ($productData) {
            return $productData['sku'];
        }, $queryBuilder->getQuery()->execute()));
    }

    public function modifyDataProvider(): array
    {
        return [
            'config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => LoadCustomerUserData::EMAIL,
                'expectedData' => [
                    'product-1',
                    'product-5',
                    'product-6',
                    'продукт-7',
                    'продукт-9',
                ]
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => LoadCustomerUserData::EMAIL,
                'expectedData' => [
                    'product-1',
                    'продукт-7',
                ]
            ],
            'anonymous config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => null,
                'expectedData' => [
                    'product-1',
                    'product-3',
                    'product-5',
                    'product-6',
                    'product-8',
                    'продукт-7',
                    'продукт-9',
                ]
            ],
            'anonymous config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => null,
                'expectedData' => [
                    'product-3',
                    'product-5',
                ]
            ],
            'group config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => LoadCustomerUserData::GROUP2_EMAIL,
                'expectedData' => [
                    'product-1',
                    'product-3',
                    'product-6',
                    'product-8',
                    'продукт-7',
                    'продукт-9',
                ]
            ],
            'customer without group and config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => LoadCustomerUserData::ORPHAN_EMAIL,
                'expectedData' => [
                    'product-1',
                    'product-2',
                    'product-3',
                    'product-4',
                    'product-5',
                    'product-6',
                    'product-8',
                    'продукт-7',
                    'продукт-9',
                ]
            ],
            'customer without group and config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => LoadCustomerUserData::ORPHAN_EMAIL,
                'expectedData' => [
                    'product-2',
                    'product-3',
                    'product-4',
                ]
            ],
        ];
    }

    public function testVisibilityProductSystemConfigurationPathNotSet()
    {
        $this->setUpForModifyMethodTests();

        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $message = sprintf('%s::productConfigPath not configured', get_class($this->modifier));
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($message);
        $this->modifier->modify($queryBuilder);
    }

    public function testVisibilityProductCategoryConfigurationPathNotSet()
    {
        $this->setUpForModifyMethodTests();

        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $message = sprintf('%s::categoryConfigPath not configured', get_class($this->modifier));
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($message);
        $this->modifier->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->modifier->modify($queryBuilder);
    }

    public function testNotVisibleForAnonymousCanBeFiltered()
    {
        $this->initClient();
        $this->loadFixtures([LoadProductVisibilityScopedData::class]);
        $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
        $this->prepareModifierForAnonymousRestrictionTests();
        $queryBuilder = $this->getProductRepository()->createQueryBuilder('product')->select('product.sku');

        $website =  $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $this->modifier->restrictForAnonymous($queryBuilder, $website);

        $actual = $queryBuilder->getQuery()->getArrayResult();
        $actualProductSkus = array_map(function ($item) {
            return $item['sku'];
        }, $actual);
        $this->assertCount(7, $actualProductSkus);

        $expected = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_5,
            LoadProductData::PRODUCT_6,
            LoadProductData::PRODUCT_7,
            LoadProductData::PRODUCT_8,
            LoadProductData::PRODUCT_9,
        ];
        foreach ($expected as $productReference) {
            $product = $this->getReference($productReference);
            $this->assertContains($product->getSku(), $actualProductSkus);
        }
    }

    public function testNotVisibleForAnonymousFallbackCategoryFiltered()
    {
        $this->initClient();
        $this->loadFixtures([LoadProductVisibilityFallbackCategoryForAnonymousData::class]);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->prepareModifierForAnonymousRestrictionTests();
        $queryBuilder = $this->getProductRepository()->createQueryBuilder('product')->select('product.sku');

        $website =  $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $this->modifier->restrictForAnonymous($queryBuilder, $website);

        $actual = $queryBuilder->getQuery()->getArrayResult();
        $actualProductSkus = array_map(function ($item) {
            return $item['sku'];
        }, $actual);
        $this->assertCount(4, $actualProductSkus);

        $expected = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_2,
            LoadProductData::PRODUCT_5,
            LoadProductData::PRODUCT_9,
        ];
        foreach ($expected as $productReference) {
            $product = $this->getReference($productReference);
            $this->assertContains($product->getSku(), $actualProductSkus);
        }
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class);
    }

    private function setUpForModifyMethodTests()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );

        $this->loadFixtures([
            LoadCustomerUserData::class,
            LoadCategoryVisibilityData::class,
            LoadProductVisibilityData::class
        ]);

        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();

        $this->configManager = $this->createMock(ConfigManager::class);

        $this->modifier = new ProductVisibilityQueryBuilderModifier(
            $this->configManager,
            $this->getContainer()->get('oro_scope.scope_manager'),
            $this->getContainer()->get('oro_entity.doctrine_helper')
        );
    }

    private function prepareModifierForAnonymousRestrictionTests()
    {
        $this->modifier = new ProductVisibilityQueryBuilderModifier(
            self::getConfigManager(null),
            $this->getContainer()->get('oro_scope.scope_manager'),
            $this->getContainer()->get('oro_entity.doctrine_helper')
        );

        $this->modifier->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->modifier->setCategoryVisibilitySystemConfigurationPath(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH);
        $this->modifier->setVisibilityScopeProvider(
            $this->getContainer()->get('oro_visibility.provider.visibility_scope_provider')
        );
    }
}

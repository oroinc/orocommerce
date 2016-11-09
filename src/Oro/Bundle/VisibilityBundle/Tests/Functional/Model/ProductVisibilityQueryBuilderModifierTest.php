<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Model;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData as AccountLoadAccountUserData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @dbIsolation
 */
class ProductVisibilityQueryBuilderModifierTest extends WebTestCase
{
    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.category_visibility';

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

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
            LoadAccountUserData::class,
            LoadCategoryVisibilityData::class,
            LoadProductVisibilityData::class
        ]);

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->modifier = new ProductVisibilityQueryBuilderModifier(
            $this->configManager,
            $this->getContainer()->get('oro_scope.scope_manager')
        );

        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
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
        if ($user) {
            /** @var AccountUser $user */
            $user = $this->getReference($user);
            $token = new UsernamePasswordToken($user, $user->getPassword(), 'key');
            $this->client->getContainer()->get('security.token_storage')->setToken($token);
        } else {
            $this->client->getContainer()->get('security.token_storage')->setToken(null);
        }

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
                    'product.7',
                ]
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => AccountLoadAccountUserData::EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.7',
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
                    'product.7',
                    'product.8',
                ]
            ],
            'anonymous config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => null,
                'expectedData' => [
                    'product.2',
                    'product.3',
                ]
            ],
            'group config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => AccountLoadAccountUserData::GROUP2_EMAIL,
                'expectedData' => [
                    'product.1',
                    'product.3',
                    'product.6',
                    'product.7',
                    'product.8',
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
                    'product.7',
                    'product.8',
                ]
            ],
            'account without group and config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => AccountLoadAccountUserData::ORPHAN_EMAIL,
                'expectedData' => [
                    'product.2',
                    'product.3',
                    'product.4',
                ]
            ],
        ];
    }

    public function testVisibilityProductSystemConfigurationPathNotSet()
    {
        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $message = sprintf('%s::productConfigPath not configured', get_class($this->modifier));
        $this->setExpectedException('\LogicException', $message);
        $this->modifier->modify($queryBuilder);
    }

    public function testVisibilityProductCategoryConfigurationPathNotSet()
    {
        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $message = sprintf('%s::categoryConfigPath not configured', get_class($this->modifier));
        $this->setExpectedException('\LogicException', $message);
        $this->modifier->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->modifier->modify($queryBuilder);
    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class);
    }
}

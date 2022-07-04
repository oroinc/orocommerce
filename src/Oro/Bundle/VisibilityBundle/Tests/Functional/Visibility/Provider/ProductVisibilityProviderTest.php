<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAnonymousCustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityScopedData;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData as LoadWebsiteDataMigration;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class ProductVisibilityProviderTest extends WebTestCase
{
    private const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.product_visibility';
    private const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.category_visibility';
    private const QUERY_BUFFER_SIZE = 10000;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductVisibilityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadWebsiteData::class,
            LoadCustomerUserData::class,
            LoadCategoryVisibilityData::class,
            LoadProductVisibilityScopedData::class
        ]);

        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ProductVisibilityProvider(
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->configManager
        );
        $this->provider->setVisibilityScopeProvider(
            $this->getContainer()->get('oro_visibility.provider.visibility_scope_provider')
        );
        $this->provider->setQueryBufferSize(self::QUERY_BUFFER_SIZE);

        $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
    }

    private function getAnonymousCustomerGroupId(): int
    {
        $customerGroupRepository = $this->getContainer()->get('doctrine')
            ->getRepository(CustomerGroup::class);

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $customerGroupRepository
            ->findOneBy(['name' => LoadAnonymousCustomerGroup::GROUP_NAME_NON_AUTHENTICATED]);

        return $customerGroup->getId();
    }

    public function testGetCustomerVisibilitiesForProducts()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::HIDDEN, VisibilityInterface::HIDDEN);

        $this->provider->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->provider->setCategoryVisibilitySystemConfigurationPath(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $expectedCustomersVisibilities = [
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-4')->getId(),
                'customerId' => $this->getReference('customer.orphan')->getId(),
            ],
        ];

        $this->assertEquals(
            $expectedCustomersVisibilities,
            $this->getActualResult($this->provider->getCustomerVisibilitiesForProducts(
                [
                    $this->getReference('product-1'),
                    $this->getReference('product-4'),
                ],
                $this->getDefaultWebsiteId()
            ))
        );
    }

    public function testGetCustomerVisibilitiesForProductsWhenCustomerGroupVisibilityDiffersProduct()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::VISIBLE, VisibilityInterface::VISIBLE);

        $this->provider->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->provider->setCategoryVisibilitySystemConfigurationPath(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $expectedCustomersVisibilities = [
            [
                'productId' => $this->getReference('product-3')->getId(),
                'customerId' => $this->getReference('customer.level_1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-3')->getId(),
                'customerId' => $this->getReference('customer.level_1.3')->getId(),
            ],
            [
                'productId' => $this->getReference('product-5')->getId(),
                'customerId' => $this->getReference('customer.level_1.2')->getId(),
            ],
            [
                'productId' => $this->getReference('product-5')->getId(),
                'customerId' => $this->getReference('customer.level_1.2.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-5')->getId(),
                'customerId' => $this->getReference('customer.level_1.2.1.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-5')->getId(),
                'customerId' => $this->getReference('customer.level_1.3')->getId(),
            ],
        ];

        $this->assertEquals(
            $expectedCustomersVisibilities,
            $this->getActualResult($this->provider->getCustomerVisibilitiesForProducts(
                [
                    $this->getReference('product-3'),
                    $this->getReference('product-5')
                ],
                $this->getDefaultWebsiteId()
            ))
        );
    }

    public function testGetCustomerVisibilitiesForProductsWhenCustomerGroupVisibilityDiffers()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::VISIBLE, VisibilityInterface::VISIBLE);

        $this->provider->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->provider->setCategoryVisibilitySystemConfigurationPath(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $expectedCustomersVisibilities = [
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.3')->getId(),
            ],
            [
                'productId' => $this->getReference('product-2')->getId(),
                'customerId' => $this->getReference('customer.level_1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-2')->getId(),
                'customerId' => $this->getReference('customer.level_1.2')->getId(),
            ],
            [
                'productId' => $this->getReference('product-2')->getId(),
                'customerId' => $this->getReference('customer.level_1.2.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-2')->getId(),
                'customerId' => $this->getReference('customer.level_1.2.1.1')->getId(),
            ],
        ];

        $this->assertEquals(
            $expectedCustomersVisibilities,
            $this->getActualResult($this->provider->getCustomerVisibilitiesForProducts(
                [
                    $this->getReference('product-1'),
                    $this->getReference('product-2')
                ],
                $this->getDefaultWebsiteId()
            ))
        );
    }

    public function testGetCustomerVisibilitiesForProductsWhenCustomerGroupVisibilityDiffersAndInversed()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::HIDDEN, VisibilityInterface::VISIBLE);

        $this->provider->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->provider->setCategoryVisibilitySystemConfigurationPath(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $expectedCustomersVisibilities = [
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => 1
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.orphan')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.1.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.1.2')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.2')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.2.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.2.1.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.3')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.3.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.3.1.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.4')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.4.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1.4.1.1')->getId(),
            ],
            [
                'productId' => $this->getReference('product-1')->getId(),
                'customerId' => $this->getReference('customer.level_1_1')->getId(),
            ],
        ];

        $this->assertEquals(
            $expectedCustomersVisibilities,
            $this->getActualResult($this->provider->getCustomerVisibilitiesForProducts(
                [
                    $this->getReference('product-1'),
                ],
                $this->getDefaultWebsiteId()
            ))
        );
    }

    private function getDefaultWebsiteId(): int
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(Website::class)
            ->findOneBy(['name' => LoadWebsiteDataMigration::DEFAULT_WEBSITE_NAME])
            ->getId();
    }

    public function testGetNewUserAndAnonymousVisibilitiesForProducts()
    {
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH],
                ['oro_customer.anonymous_customer_group']
            )
            ->willReturnOnConsecutiveCalls(
                VisibilityInterface::HIDDEN,
                VisibilityInterface::HIDDEN,
                $this->getAnonymousCustomerGroupId()
            );

        $this->provider->setProductVisibilitySystemConfigurationPath(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->provider->setCategoryVisibilitySystemConfigurationPath(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $expectedVisibilities = [
            [
                'productId' => $this->getReference('product-3')->getId(),
                'visibility_new' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                'visibility_anonymous' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                'is_visible_by_default' => BaseVisibilityResolved::VISIBILITY_HIDDEN
            ],
            [
                'productId' => $this->getReference('product-5')->getId(),
                'visibility_new' => BaseVisibilityResolved::VISIBILITY_HIDDEN,
                'visibility_anonymous' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                'is_visible_by_default' => BaseVisibilityResolved::VISIBILITY_HIDDEN
            ],
        ];

        $this->assertEquals(
            $expectedVisibilities,
            $this->provider->getNewUserAndAnonymousVisibilitiesForProducts([
                $this->getReference('product-3'),
                $this->getReference('product-5'),
            ], $this->getDefaultWebsiteId())
        );
    }

    private function getActualResult(\Traversable $items): array
    {
        if (!is_array($items)) {
            $items = iterator_to_array($items, false);
        }

        usort($items, [$this, 'compare']);

        return $items;
    }

    private function compare(array $a, array $b): int
    {
        if ($a['productId'] === $b['productId']) {
            return $a['customerId'] - $b['customerId'];
        }

        return $a['productId'] - $b['productId'];
    }
}

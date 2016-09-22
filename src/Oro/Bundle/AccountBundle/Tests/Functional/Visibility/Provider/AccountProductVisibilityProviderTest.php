<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Visibility\Provider;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Migrations\Data\ORM\LoadAnonymousAccountGroup;
use Oro\Bundle\AccountBundle\Visibility\Provider\AccountProductVisibilityProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountProductVisibilityProviderTest extends WebTestCase
{
    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_account.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_account.category_visibility';

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var AccountProductVisibilityProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ]);

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AccountProductVisibilityProvider(
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->configManager
        );

        $this->getContainer()->get('oro_account.visibility.cache.product.cache_builder')->buildCache();
    }

    /**
     * @return int
     */
    private function getAnonymousAccountGroupId()
    {
        $accountGroupRepository = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroAccountBundle:AccountGroup')
            ->getRepository('OroAccountBundle:AccountGroup');

        /** @var AccountGroup $accountGroup */
        $accountGroup = $accountGroupRepository
            ->findOneBy(['name' => LoadAnonymousAccountGroup::GROUP_NAME_NON_AUTHENTICATED]);

        return $accountGroup->getId();
    }

    public function testGetAccountVisibilitiesForProducts()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::HIDDEN, VisibilityInterface::HIDDEN);

        $this->provider->setProductVisibilitySystemConfigurationPath(static::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->provider->setCategoryVisibilitySystemConfigurationPath(static::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $expectedAccountsVisibilities = [
            [
                'productId' => $this->getReference('product.1')->getId(),
                'accountId' => $this->getReference('account.level_1')->getId(),
            ],
            [
                'productId' => $this->getReference('product.4')->getId(),
                'accountId' => $this->getReference('account.orphan')->getId(),
            ],
        ];

        $this->assertEquals(
            $expectedAccountsVisibilities,
            $this->provider->getAccountVisibilitiesForProducts(
                [
                    $this->getReference('product.1')->getId(),
                    $this->getReference('product.4')->getId()
                ],
                $this->getDefaultWebsiteId()
            )
        );
    }

    /**
     * @return int
     */
    private function getDefaultWebsiteId()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroWebsiteBundle:Website')
            ->getRepository('OroWebsiteBundle:Website')
            ->findOneBy(['name' => LoadWebsiteData::DEFAULT_WEBSITE_NAME])
            ->getId();
    }

    public function testGetNewUserAndAnonymousVisibilitiesForProducts()
    {
        $this->configManager
            ->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH],
                ['oro_account.anonymous_account_group']
            )
            ->willReturnOnConsecutiveCalls(
                VisibilityInterface::HIDDEN,
                VisibilityInterface::HIDDEN,
                $this->getAnonymousAccountGroupId()
            );

        $this->provider->setProductVisibilitySystemConfigurationPath(static::PRODUCT_VISIBILITY_CONFIGURATION_PATH);
        $this->provider->setCategoryVisibilitySystemConfigurationPath(static::CATEGORY_VISIBILITY_CONFIGURATION_PATH);

        $expectedVisibilities = [
            [
                'productId' => $this->getReference('product.3')->getId(),
                'visibility_new' => 1,
                'visibility_anonymous' => 1,
            ],
            [
                'productId' => $this->getReference('product.5')->getId(),
                'visibility_new' => 0,
                'visibility_anonymous' => 1,
            ],
        ];

        $this->assertEquals(
            $expectedVisibilities,
            $this->provider->getNewUserAndAnonymousVisibilitiesForProducts([
                $this->getReference('product.3')->getId(),
                $this->getReference('product.5')->getId(),
            ], $this->getDefaultWebsiteId())
        );
    }
}

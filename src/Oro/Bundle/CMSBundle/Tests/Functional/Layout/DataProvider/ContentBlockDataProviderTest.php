<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Layout\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Layout\DataProvider\ContentBlockDataProvider;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadTextContentVariantsData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadThemeConfigurationData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration as ThemeConfigurationEntity;

/**
 * @dbIsolationPerTest
 */
class ContentBlockDataProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ContentBlockDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadTextContentVariantsData::class,
            LoadCustomerUser::class,
            LoadCustomerVisitors::class,
            LoadThemeConfigurationData::class,
        ]);
        $this->provider = self::getContainer()->get('oro_cms.provider.content_block_provider');
    }

    #[\Override]
    protected function tearDown(): void
    {
        self::getContainer()->get('security.token_storage')->setToken(null);
        self::getContainer()->get(FrontendHelper::class)->resetRequestEmulation();
        parent::tearDown();
    }

    private function getThemeConfiguration(): ThemeConfigurationEntity
    {
        return $this->getThemeConfigurationEntityManager()->find(
            ThemeConfigurationEntity::class,
            self::getConfigManager()->get(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
        );
    }

    private function getThemeConfigurationEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(ThemeConfigurationEntity::class);
    }

    private function setContentBlockForThemeConfiguration(): void
    {
        $this->getThemeConfiguration()->addConfigurationOption(
            ThemeConfiguration::buildOptionKey('header', 'promotional_content'),
            $this->getReference('content_block_1')->getId()
        );
        $this->getThemeConfigurationEntityManager()->flush();
    }

    public function testGetPromotionalBlockAliasForVisitors(): void
    {
        /** @var CustomerVisitor $visitor */
        $visitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);
        self::getContainer()
            ->get('security.token_storage')
            ->setToken(new AnonymousCustomerUserToken(
                $visitor,
                [],
                $this->getReference(LoadOrganization::ORGANIZATION)
            ));

        $this->setContentBlockForThemeConfiguration();

        self::assertEquals('content_block_1', $this->provider->getPromotionalBlockAlias());
    }

    public function testGetPromotionalBlockAliasForCustomerUser(): void
    {
        /** @var CustomerUser $user */
        $user = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        self::getContainer()
            ->get('security.token_storage')
            ->setToken(new UsernamePasswordOrganizationToken(
                $user,
                'k',
                $user->getOrganization(),
                $user->getUserRoles()
            ));

        $this->setContentBlockForThemeConfiguration();

        self::assertEquals('content_block_1', $this->provider->getPromotionalBlockAlias());
    }

    public function testGetPromotionalBlockAliasForAnonymous(): void
    {
        self::getContainer()->get('security.token_storage')->setToken(null);

        $this->setContentBlockForThemeConfiguration();

        self::assertEquals('content_block_1', $this->provider->getPromotionalBlockAlias());
    }

    public function testGetPromotionalBlockAliasFromThemeConfigurationForAnonymous(): void
    {
        self::getContainer()->get('security.token_storage')->setToken(null);

        $configManager = self::getConfigManager();
        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
            $this->getReference(LoadThemeConfigurationData::THEME_CONFIGURATION_1)->getId()
        );
        $configManager->flush();

        self::assertEquals('content_block_1', $this->provider->getPromotionalBlockAlias());
    }
}

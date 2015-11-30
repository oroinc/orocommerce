<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\Testing\WebTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData as AccountLoadAccountUserData;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * @dbIsolation
 */
class ProductVisibilityQueryBuilderModifierTest extends WebTestCase
{
    const VISIBILITY_SYSTEM_CONFIGURATION_PATH = 'oro_b2b_account.product_visibility';

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
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityResolvedData',
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
        ]);

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->websiteManager = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()->getMock();

        $this->modifier = new ProductVisibilityQueryBuilderModifier(
            $this->configManager,
            $this->tokenStorage,
            $this->websiteManager
        );
    }

    /**
     * @dataProvider modifyDataProvider
     *
     * @param string $configValue
     * @param string|null $user
     * @param string $website
     * @param array $expectedData
     */
    public function testModify($configValue, $user, $website, $expectedData)
    {
        if ($user) {
            $user = $this->getReference($user);
        }

        $this->setupTokenStorage($user);

        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(static::VISIBILITY_SYSTEM_CONFIGURATION_PATH)
            ->willReturn($configValue);

        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($this->getReference($website));

        $this->modifier->setVisibilitySystemConfigurationPath(static::VISIBILITY_SYSTEM_CONFIGURATION_PATH);

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
                'website' => 'US',
                'expectedData' => [
                    'product.1',
                    'product.5',
                ]
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => AccountLoadAccountUserData::EMAIL,
                'website' => 'US',
                'expectedData' => [
                    'product.1',
                    'product.5',
                ]
            ],
            'anonymous config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => null,
                'website' => 'US',
                'expectedData' => [
                    'product.1',
                    'product.2',
                    'product.3',
                    'product.5',
                ]
            ],
            'anonymous config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => null,
                'website' => 'US',
                'expectedData' => [
                    'product.2',
                    'product.3',
                    'product.5',
                ]
            ],
        ];
    }

    public function testVisibilitySystemConfigurationPathNotSet()
    {
        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->select('p.sku')->orderBy('p.sku');

        $message = sprintf('%s::visibilitySystemConfigurationPath not configured', get_class($this->modifier));
        $this->setExpectedException('\LogicException', $message);
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
     * @return \OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product');
    }
}

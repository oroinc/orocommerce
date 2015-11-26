<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Security;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\Testing\WebTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Acl\Voter\ProductVisibilityVoter;
use OroB2B\Bundle\AccountBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData as AccountLoadAccountUserData;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductVisibilityTest extends WebTestCase
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
     * @var ProductVisibilityVoter
    */
    protected $voter;

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
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityResolvedData',
        ]);

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->modifier = new ProductVisibilityQueryBuilderModifier(
            $this->configManager,
            $this->tokenStorage
        );

        $this->modifier->setVisibilitySystemConfigurationPath(static::VISIBILITY_SYSTEM_CONFIGURATION_PATH);
        $this->voter = $this->getContainer()->get('orob2b_product_viisbility.acl.voter.account');
        $this->voter->setModifier($this->modifier);
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
            $user = $this->getReference($user);
        }
        $this->setupTokenStorage($user);
        $this->configManager->expects($this->any())
            ->method('get')
            ->with(static::VISIBILITY_SYSTEM_CONFIGURATION_PATH)
            ->willReturn($configValue);

        foreach ($expectedData as $productKey => $accessResult) {
            $product = $this->getReference($productKey);
            $res = $this->voter->vote(
                $this->tokenStorage->getToken(),
                $product,
                [ProductVisibilityVoter::ATTRIBUTE_VIEW]
            );
            $this->assertEquals($accessResult, $res);
        }
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
                    'product.1' => VoterInterface::ACCESS_GRANTED,
                    'product.2' => VoterInterface::ACCESS_DENIED,
                    'product.3' => VoterInterface::ACCESS_DENIED,
                    'product.4' => VoterInterface::ACCESS_DENIED,
                    'product.5' => VoterInterface::ACCESS_GRANTED,
                ]
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => AccountLoadAccountUserData::EMAIL,
                'expectedData' => [
                    'product.1' => VoterInterface::ACCESS_GRANTED,
                    'product.2' => VoterInterface::ACCESS_DENIED,
                    'product.3' => VoterInterface::ACCESS_DENIED,
                    'product.4' => VoterInterface::ACCESS_DENIED,
                    'product.5' => VoterInterface::ACCESS_GRANTED,
                ]
            ],
            'anonymous config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'user' => null,
                'expectedData' => [
                    'product.1' => VoterInterface::ACCESS_GRANTED,
                    'product.2' => VoterInterface::ACCESS_GRANTED,
                    'product.3' => VoterInterface::ACCESS_GRANTED,
                    'product.4' => VoterInterface::ACCESS_DENIED,
                    'product.5' => VoterInterface::ACCESS_GRANTED,
                ]
            ],
            'anonymous config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'user' => null,
                'expectedData' => [
                    'product.1' => VoterInterface::ACCESS_DENIED,
                    'product.2' => VoterInterface::ACCESS_GRANTED,
                    'product.3' => VoterInterface::ACCESS_GRANTED,
                    'product.4' => VoterInterface::ACCESS_DENIED,
                    'product.5' => VoterInterface::ACCESS_GRANTED,
                ]
            ],
        ];
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

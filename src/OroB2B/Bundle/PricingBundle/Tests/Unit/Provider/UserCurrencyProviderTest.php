<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserCurrency;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class UserCurrencyProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * @var BaseUserManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userManager;

    /**
     * @var UserCurrencyProvider
     */
    protected $userCurrencyProvider;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteManager = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\BaseUserManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCurrencyProvider = new UserCurrencyProvider(
            $this->session,
            $this->tokenStorage,
            $this->configManager,
            $this->websiteManager,
            $this->userManager
        );
    }

    public function testGetUserCurrencyNoWebsite()
    {
        $this->configManager->expects($this->never())
            ->method($this->anything());
        $this->assertNull($this->userCurrencyProvider->getUserCurrency());
    }

    public function testGetUserCurrencyLoggedUser()
    {
        /** @var Website $website **/
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);

        $currency = new AccountUserCurrency();
        $currency->setCurrency('EUR');

        $user = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $user->expects($this->once())
            ->method('getCurrencyByWebsite')
            ->with($website)
            ->willReturn($currency);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_pricing.enabled_currencies', [])
            ->willReturn(['EUR', 'USD']);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($currency->getCurrency(), $this->userCurrencyProvider->getUserCurrency($website));
    }

    public function testGetUserCurrencyLoggedUserUnsupportedCurrency()
    {
        /** @var Website $website **/
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);
        $user = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $currentCurrency = new AccountUserCurrency();
        $currentCurrency->setCurrency('UAH');

        $user->expects($this->once())
            ->method('getCurrencyByWebsite')
            ->with($website)
            ->willReturn($currentCurrency);
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_b2b_pricing.enabled_currencies', [], false, ['EUR', 'USD']],
                    ['oro_b2b_pricing.default_currency', false, false, 'EUR']
                ]
            );

        $this->assertEquals('EUR', $this->userCurrencyProvider->getUserCurrency());
    }

    public function testGetUserCurrencyAnonymousHasCurrencyForWebsite()
    {
        /** @var Website $website **/
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);
        $currency = 'EUR';
        $sessionCurrencies = [1 => $currency, 2 => 'GBP'];

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_pricing.enabled_currencies', [])
            ->willReturn(['EUR', 'USD']);
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyProvider::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);

        $this->assertEquals($currency, $this->userCurrencyProvider->getUserCurrency($website));
    }

    public function testGetUserCurrencyAnonymousNoCurrencyForWebsite()
    {
        /** @var Website $website **/
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);
        $sessionCurrencies = null;

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_b2b_pricing.enabled_currencies', [], false, ['EUR', 'USD']],
                    ['oro_b2b_pricing.default_currency', false, false, 'EUR']
                ]
            );
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyProvider::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);

        $this->assertEquals('EUR', $this->userCurrencyProvider->getUserCurrency($website));
    }

    public function testGetUserCurrencyAnonymousHasUnsupportedCurrencyForWebsite()
    {
        /** @var Website $website **/
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);
        $sessionCurrencies = [1 => 'UAH', 2 => 'GBP'];

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_b2b_pricing.enabled_currencies', [], false, ['EUR', 'USD']],
                    ['oro_b2b_pricing.default_currency', false, false, 'EUR']
                ]
            );
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyProvider::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);

        $this->assertEquals('EUR', $this->userCurrencyProvider->getUserCurrency($website));
    }

    public function testSaveSelectedCurrencyLoggedUser()
    {
        $currency = 'USD';
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website **/
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');

        $user = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $user->expects($this->once())
            ->method('setWebsiteCurrency')
            ->with($website, $currency);
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->once())
            ->method('flush');
        $this->userManager->expects($this->once())
            ->method('getStorageManager')
            ->willReturn($em);

        $this->userCurrencyProvider->saveSelectedCurrency($currency, $website);
    }

    public function testSaveSelectedCurrencyAnonymousUser()
    {
        $currency = 'USD';
        $sessionCurrencies = [2 => 'GBP'];
        /** @var Website $website **/
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyProvider::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);
        $this->session->expects($this->once())
            ->method('set')
            ->with(UserCurrencyProvider::SESSION_CURRENCIES, [1 => 'USD', 2 => 'GBP']);

        $this->userCurrencyProvider->saveSelectedCurrency($currency, $website);
    }

    public function testSaveSelectedCurrencyAnonymousUserNoWebsite()
    {
        $currency = 'USD';
        $sessionCurrencies = [2 => 'GBP'];
        /** @var Website $website **/
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyProvider::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);
        $this->session->expects($this->once())
            ->method('set')
            ->with(UserCurrencyProvider::SESSION_CURRENCIES, [1 => 'USD', 2 => 'GBP']);

        $this->userCurrencyProvider->saveSelectedCurrency($currency);
    }
}

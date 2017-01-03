<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Manager;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserCurrencyManagerTest extends \PHPUnit_Framework_TestCase
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
     * @var CurrencyProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyProvider;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * @var BaseUserManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userManager;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->setMethods(['getDefaultCurrency', 'getCurrencyList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->websiteManager = $this->getMockBuilder('Oro\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\BaseUserManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCurrencyManager = new UserCurrencyManager(
            $this->session,
            $this->tokenStorage,
            $this->currencyProvider,
            $this->websiteManager,
            $this->userManager
        );
    }

    public function testGetUserCurrencyNoWebsite()
    {
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->currencyProvider->expects(static::any())
            ->method('getCurrencyList')
            ->willReturn(['EUR', 'USD']);
        $this->currencyProvider->expects(static::any())
            ->method('getDefaultCurrency')
            ->willReturn('EUR');

        $this->assertEquals('EUR', $this->userCurrencyManager->getUserCurrency());
    }

    public function testGetUserCurrencyLoggedUser()
    {
        /** @var Website $website */
        $website = $this->getEntity('Oro\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);

        $userWebsiteSettings = new CustomerUserSettings($website);
        $userWebsiteSettings->setCurrency('EUR');

        $user = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $user->expects($this->once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($userWebsiteSettings);
        $this->currencyProvider->expects($this->once())
            ->method('getCurrencyList')
            ->willReturn(['EUR', 'USD']);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($userWebsiteSettings->getCurrency(), $this->userCurrencyManager->getUserCurrency($website));
    }

    public function testGetUserCurrencyLoggedUserUnsupportedCurrency()
    {
        /** @var Website $website */
        $website = $this->getEntity('Oro\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);
        $user = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $userWebsiteSettings = new CustomerUserSettings($website);
        $userWebsiteSettings->setCurrency('UAH');

        $user->expects($this->once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($userWebsiteSettings);
        $this->currencyProvider->expects(static::any())
            ->method('getCurrencyList')
            ->willReturn(['EUR', 'USD']);
        $this->currencyProvider->expects(static::any())
            ->method('getDefaultCurrency')
            ->willReturn('EUR');

        $this->assertEquals('EUR', $this->userCurrencyManager->getUserCurrency());
    }

    public function testGetUserCurrencyAnonymousHasCurrencyForWebsite()
    {
        /** @var Website $website */
        $website = $this->getEntity('Oro\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);
        $currency = 'EUR';
        $sessionCurrencies = [1 => $currency, 2 => 'GBP'];

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $this->currencyProvider->expects($this->once())
            ->method('getCurrencyList')
            ->willReturn(['EUR', 'USD']);
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyManager::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);

        $this->assertEquals($currency, $this->userCurrencyManager->getUserCurrency($website));
    }

    public function testGetUserCurrencyAnonymousNoCurrencyForWebsite()
    {
        /** @var Website $website */
        $website = $this->getEntity('Oro\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);
        $sessionCurrencies = null;

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $this->currencyProvider->expects(static::any())
            ->method('getCurrencyList')
            ->willReturn(['EUR', 'USD']);
        $this->currencyProvider->expects(static::any())
            ->method('getDefaultCurrency')
            ->willReturn('EUR');
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyManager::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);

        $this->assertEquals('EUR', $this->userCurrencyManager->getUserCurrency($website));
    }

    public function testGetUserCurrencyAnonymousHasUnsupportedCurrencyForWebsite()
    {
        /** @var Website $website */
        $website = $this->getEntity('Oro\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);
        $sessionCurrencies = [1 => 'UAH', 2 => 'GBP'];

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $this->currencyProvider->expects(static::any())
            ->method('getCurrencyList')
            ->willReturn(['EUR', 'USD']);
        $this->currencyProvider->expects(static::any())
            ->method('getDefaultCurrency')
            ->willReturn('EUR');
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyManager::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);

        $this->assertEquals('EUR', $this->userCurrencyManager->getUserCurrency($website));
    }

    public function testSaveSelectedCurrencyLoggedUser()
    {
        $currency = 'USD';
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->createMock('Oro\Bundle\WebsiteBundle\Entity\Website');

        $user = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $user->expects($this->once())
            ->method('setWebsiteSettings');
        $em = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->once())
            ->method('flush');
        $this->userManager->expects($this->once())
            ->method('getStorageManager')
            ->willReturn($em);

        $this->userCurrencyManager->saveSelectedCurrency($currency, $website);
    }

    public function testSaveSelectedCurrencyAnonymousUser()
    {
        $currency = 'USD';
        $sessionCurrencies = [2 => 'GBP'];
        /** @var Website $website */
        $website = $this->getEntity('Oro\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyManager::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);
        $this->session->expects($this->once())
            ->method('set')
            ->with(UserCurrencyManager::SESSION_CURRENCIES, [1 => 'USD', 2 => 'GBP']);

        $this->userCurrencyManager->saveSelectedCurrency($currency, $website);
    }

    public function testSaveSelectedCurrencyAnonymousUserNoWebsite()
    {
        $currency = 'USD';
        $sessionCurrencies = [2 => 'GBP'];
        /** @var Website $website */
        $website = $this->getEntity('Oro\Bundle\WebsiteBundle\Entity\Website', ['id' => 1]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserCurrencyManager::SESSION_CURRENCIES)
            ->willReturn($sessionCurrencies);
        $this->session->expects($this->once())
            ->method('set')
            ->with(UserCurrencyManager::SESSION_CURRENCIES, [1 => 'USD', 2 => 'GBP']);

        $this->userCurrencyManager->saveSelectedCurrency($currency);
    }
}

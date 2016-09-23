<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;

class QuoteAddressSecurityProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var QuoteAddressSecurityProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QuoteAddressProvider */
    protected $quoteAddressProvider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteAddressProvider = $this->getMockBuilder('Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new QuoteAddressSecurityProvider(
            $this->securityFacade,
            $this->quoteAddressProvider,
            'AccountQuoteClass',
            'AccountUserQuoteClass'
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->provider, $this->quoteAddressProvider);
    }

    /**
     * @dataProvider manualEditDataProvider
     * @param string $type
     * @param string $permissionName
     * @param bool $permission
     */
    public function testIsManualEditGranted($type, $permissionName, $permission)
    {
        $this->securityFacade->expects($this->atLeastOnce())->method('isGranted')->with($permissionName)
            ->willReturn($permission);

        $this->assertEquals($permission, $this->provider->isManualEditGranted($type));
    }

    /**
     * @return array
     */
    public function manualEditDataProvider()
    {
        return [
            ['shipping', 'oro_quote_address_shipping_allow_manual_backend', true],
            ['shipping', 'oro_quote_address_shipping_allow_manual_backend', false],
        ];
    }

    /**
     * @dataProvider permissionsDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string $userClass
     * @param string $addressType
     * @param array|null $isGranted
     * @param bool $hasAccountAddresses
     * @param bool $hasAccountUserAddresses
     * @param bool $hasEntity
     * @param bool $isAddressGranted
     * @param bool $isAccountAddressGranted
     * @param bool $isAccountUserAddressGranted
     */
    public function testIsAddressGranted(
        $userClass,
        $addressType,
        $isGranted,
        $hasAccountAddresses,
        $hasAccountUserAddresses,
        $hasEntity,
        $isAddressGranted,
        $isAccountAddressGranted,
        $isAccountUserAddressGranted
    ) {
        $this->quoteAddressProvider->expects($this->any())->method('getAccountAddresses')
            ->willReturn($hasAccountAddresses);
        $this->quoteAddressProvider->expects($this->any())->method('getAccountUserAddresses')
            ->willReturn($hasAccountUserAddresses);

        $this->securityFacade->expects($this->any())->method('getLoggedUser')->willReturn(new $userClass);
        $this->securityFacade->expects($this->any())->method('isGranted')->with($this->isType('string'))
            ->will($this->returnValueMap((array)$isGranted));

        $quote = null;
        $account = null;
        $accountUser = null;
        if ($hasEntity) {
            $account = new Account();
            $accountUser = new AccountUser();
        }
        $quote = (new Quote())->setAccount($account)->setAccountUser($accountUser);

        $this->assertEquals(
            $isAddressGranted,
            $this->provider->isAddressGranted($quote, $addressType)
        );
        $this->assertEquals(
            $isAccountAddressGranted,
            $this->provider->isAccountAddressGranted($addressType, $account)
        );
        $this->assertEquals(
            $isAccountUserAddressGranted,
            $this->provider->isAccountUserAddressGranted($addressType, $accountUser)
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function permissionsDataProvider()
    {
        $finder = new Finder();
        $yaml = new Parser();
        $data = [];

        $finder->files()->in(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures');
        foreach ($finder as $file) {
            $data = $data + $yaml->parse(file_get_contents($file));
        }

        return $data;
    }
}

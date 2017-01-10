<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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
            'CustomerQuoteClass',
            'CustomerUserQuoteClass'
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
     * @param bool $hasCustomerAddresses
     * @param bool $hasCustomerUserAddresses
     * @param bool $hasEntity
     * @param bool $isAddressGranted
     * @param bool $isCustomerAddressGranted
     * @param bool $isCustomerUserAddressGranted
     */
    public function testIsAddressGranted(
        $userClass,
        $addressType,
        $isGranted,
        $hasCustomerAddresses,
        $hasCustomerUserAddresses,
        $hasEntity,
        $isAddressGranted,
        $isCustomerAddressGranted,
        $isCustomerUserAddressGranted
    ) {
        $this->quoteAddressProvider->expects($this->any())->method('getCustomerAddresses')
            ->willReturn($hasCustomerAddresses);
        $this->quoteAddressProvider->expects($this->any())->method('getCustomerUserAddresses')
            ->willReturn($hasCustomerUserAddresses);

        $this->securityFacade->expects($this->any())->method('getLoggedUser')->willReturn(new $userClass);
        $this->securityFacade->expects($this->any())->method('isGranted')->with($this->isType('string'))
            ->will($this->returnValueMap((array)$isGranted));

        $quote = null;
        $customer = null;
        $customerUser = null;
        if ($hasEntity) {
            $customer = new Customer();
            $customerUser = new CustomerUser();
        }
        $quote = (new Quote())->setCustomer($customer)->setCustomerUser($customerUser);

        $this->assertEquals(
            $isAddressGranted,
            $this->provider->isAddressGranted($quote, $addressType)
        );
        $this->assertEquals(
            $isCustomerAddressGranted,
            $this->provider->isCustomerAddressGranted($addressType, $customer)
        );
        $this->assertEquals(
            $isCustomerUserAddressGranted,
            $this->provider->isCustomerUserAddressGranted($addressType, $customerUser)
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

<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Yaml\Parser;

class OrderAddressSecurityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderAddressSecurityProvider */
    protected $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressProvider */
    protected $orderAddressProvider;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->orderAddressProvider = $this->getMockBuilder('Oro\Bundle\OrderBundle\Provider\OrderAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OrderAddressSecurityProvider(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->orderAddressProvider,
            'CustomerOrderClass',
            'CustomerUserOrderClass'
        );
    }

    protected function tearDown()
    {
        unset($this->authorizationChecker, $this->tokenAccessor, $this->provider, $this->orderAddressProvider);
    }

    /**
     * @dataProvider manualEditDataProvider
     * @param string $type
     * @param string $permissionName
     * @param bool $permission
     */
    public function testIsManualEditGranted($type, $permissionName, $permission)
    {
        $this->authorizationChecker->expects($this->atLeastOnce())
            ->method('isGranted')
            ->with($permissionName)
            ->willReturn($permission);

        $this->assertEquals($permission, $this->provider->isManualEditGranted($type));
    }

    /**
     * @return array
     */
    public function manualEditDataProvider()
    {
        return [
            ['billing', 'oro_order_address_billing_allow_manual_backend', true],
            ['billing', 'oro_order_address_billing_allow_manual_backend', false],
            ['shipping', 'oro_order_address_shipping_allow_manual_backend', true],
            ['shipping', 'oro_order_address_shipping_allow_manual_backend', false],
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
        $this->orderAddressProvider->expects($this->any())->method('getCustomerAddresses')
            ->willReturn($hasCustomerAddresses);
        $this->orderAddressProvider->expects($this->any())->method('getCustomerUserAddresses')
            ->willReturn($hasCustomerUserAddresses);

        $this->tokenAccessor->expects($this->any())->method('getUser')->willReturn(new $userClass);
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->with($this->isType('string'))
            ->will($this->returnValueMap((array)$isGranted));

        $order = null;
        $customer = null;
        $customerUser = null;
        if ($hasEntity) {
            $customer = new Customer();
            $customerUser = new CustomerUser();
        }
        $order = (new Order())->setCustomer($customer)->setCustomerUser($customerUser);

        $this->assertEquals(
            $isAddressGranted,
            $this->provider->isAddressGranted($order, $addressType)
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

    public function testIsManualEditGrantedForCustomerVisitor()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $type = 'billing';
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with(sprintf(OrderAddressSecurityProvider::MANUAL_EDIT_ACTION, $type))
            ->willReturn(true);

        $this->assertTrue($this->provider->isManualEditGranted($type));
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Yaml\Parser;

class OrderAddressSecurityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderAddressSecurityProvider */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FrontendHelper */
    private $frontendHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressProvider */
    private $orderAddressProvider;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->orderAddressProvider = $this->createMock(OrderAddressProvider::class);

        $this->provider = new OrderAddressSecurityProvider(
            $this->authorizationChecker,
            $this->frontendHelper,
            $this->orderAddressProvider,
            'CustomerOrderClass',
            'CustomerUserOrderClass'
        );
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

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(is_a($userClass, CustomerUser::class, true));
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
}

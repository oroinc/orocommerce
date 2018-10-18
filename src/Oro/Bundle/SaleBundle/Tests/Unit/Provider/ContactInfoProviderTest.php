<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SaleBundle\Model\ContactInfoFactory;
use Oro\Bundle\SaleBundle\Provider\ContactInfoAvailableUserOptionsProvider as UserOptionsProvider;
use Oro\Bundle\SaleBundle\Provider\ContactInfoProvider;
use Oro\Bundle\SaleBundle\Provider\ContactInfoSourceOptionsProvider;
use Oro\Bundle\SaleBundle\Provider\ContactInfoUserOptionsProvider;
use Oro\Bundle\UserBundle\Entity\User;

class ContactInfoProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NameFormatter
     */
    private $nameFormatter;

    /**
     * @var ContactInfoProvider
     */
    private $contactInfoProvider;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ContactInfoSourceOptionsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactSourceProvider;

    /**
     * @var ContactInfoUserOptionsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactInfoUserOptionsProvider;

    /**
     * @var ContactInfoFactory
     */
    private $contactInfoFactory;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->contactSourceProvider = $this->createMock(ContactInfoSourceOptionsProvider::class);
        $this->contactInfoUserOptionsProvider = $this->createMock(ContactInfoUserOptionsProvider::class);
        $this->nameFormatter = $this->createMock(NameFormatter::class);
        $this->contactInfoFactory = new ContactInfoFactory($this->nameFormatter);

        $this->contactInfoProvider = new ContactInfoProvider(
            $this->configManager,
            $this->contactSourceProvider,
            $this->contactInfoUserOptionsProvider,
            $this->contactInfoFactory
        );
    }

    public function testDontDisplay()
    {
        $this->contactSourceProvider
            ->method('getSelectedOption')
            ->willReturn(ContactInfoSourceOptionsProvider::DONT_DISPLAY);
        $contactInfo = $this->contactInfoProvider->getContactInfo();
        static::assertTrue($contactInfo->isEmpty());
    }

    public function testPreConfigured()
    {
        $this->contactSourceProvider
            ->method('getSelectedOption')
            ->willReturn(ContactInfoSourceOptionsProvider::PRE_CONFIGURED);
        $this->configManager->method('get')->willReturnMap(
            [
                ['oro_sale.guest_contact_info_text', false, false, null, 'text for anon'],
                ['oro_sale.contact_details', false, false, null, 'text for customers'],
            ]
        );

        $contactInfo = $this->contactInfoProvider->getContactInfo();
        static::assertFalse($contactInfo->isEmpty());
        static::assertEquals('text for anon', $contactInfo->getManualText());

        $contactInfo = $this->contactInfoProvider->getContactInfo(new CustomerUser());
        static::assertFalse($contactInfo->isEmpty());
        static::assertEquals('text for customers', $contactInfo->getManualText());
    }

    /**
     * @param string $defaultOption
     * @param string $selectedOption
     * @param bool   $allowChoice
     * @param array  $infoData
     *
     * @dataProvider infoFromUserOwnerDataProvider
     */
    public function testFromUserOwner($defaultOption, $selectedOption, $allowChoice, $infoData)
    {
        $this->contactSourceProvider
            ->method('getSelectedOption')
            ->willReturn(ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER);

        $this->contactInfoUserOptionsProvider
            ->method('getDefaultOption')
            ->willReturn($defaultOption);

        $this->contactInfoUserOptionsProvider
            ->method('getSelectedOption')
            ->willReturn($selectedOption);

        $user = $this->createUserMock('John Doe', '1111', 'mail@exemple.dev');
        $customerUser = new CustomerUser();
        $customerUser->setOwner($user);

        $this->configManager->method('get')->willReturnMap(
            [
                ['oro_sale.allow_user_configuration', false, false, null, $allowChoice],
                ['oro_sale.contact_info_manual_text', false, false, $user, 'user text'],
                ['oro_sale.contact_details', false, false, null, 'system text'],
                ['oro_sale.guest_contact_info_text', false, false, null, 'text for anon'],
            ]
        );
        $contactInfo = $this->contactInfoProvider->getContactInfo($customerUser);

        static::assertEquals($infoData, $contactInfo->all());
        $contactInfo = $this->contactInfoProvider->getContactInfo();
        static::assertEquals(['manual_text' => 'text for anon'], $contactInfo->all());
    }

    /**
     * @param string $defaultOption
     * @param string $selectedOption
     * @param bool   $allowChoice
     * @param array  $infoData
     *
     * @dataProvider infoFromUserOwnerDataProvider
     */
    public function testFromCustomerOwner($defaultOption, $selectedOption, $allowChoice, $infoData)
    {
        $this->contactSourceProvider
            ->method('getSelectedOption')
            ->willReturn(ContactInfoSourceOptionsProvider::CUSTOMER_OWNER);

        $this->contactInfoUserOptionsProvider
            ->method('getDefaultOption')
            ->willReturn($defaultOption);

        $this->contactInfoUserOptionsProvider
            ->method('getSelectedOption')
            ->willReturn($selectedOption);

        $user = $this->createUserMock('John Doe', '1111', 'mail@exemple.dev');
        $customer = new Customer();
        $customer->setOwner($user);
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        $this->configManager->method('get')->willReturnMap(
            [
                ['oro_sale.allow_user_configuration', false, false, null, $allowChoice],
                ['oro_sale.contact_info_manual_text', false, false, $user, 'user text'],
                ['oro_sale.contact_details', false, false, null, 'system text'],
                ['oro_sale.guest_contact_info_text', false, false, null, 'text for anon'],
            ]
        );
        $contactInfo = $this->contactInfoProvider->getContactInfo($customerUser);

        static::assertEquals($infoData, $contactInfo->all());

        $contactInfo = $this->contactInfoProvider->getContactInfo();
        static::assertEquals(['manual_text' => 'text for anon'], $contactInfo->all());
    }

    public function infoFromUserOwnerDataProvider()
    {
        return [
            [
                'defaultOption' => UserOptionsProvider::USE_USER_PROFILE_DATA,
                'selectedOption' => UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO,
                'allowChoice' => false,
                'infoData' => [
                    'email' => 'mail@exemple.dev',
                    'name' => 'John Doe',
                    'phone' => '1111',
                ],
            ],
            [
                'defaultOption' => UserOptionsProvider::USE_USER_PROFILE_DATA,
                'selectedOption' => UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO,
                'allowChoice' => true,
                'infoData' => [],
            ],
            [
                'defaultOption' => UserOptionsProvider::USE_USER_PROFILE_DATA,
                'selectedOption' => UserOptionsProvider::ENTER_MANUALLY,
                'allowChoice' => true,
                'infoData' => [
                    'manual_text' => 'user text',
                ],
            ],
            [
                'defaultOption' => ContactInfoUserOptionsProvider::USE_SYSTEM,
                'selectedOption' => UserOptionsProvider::ENTER_MANUALLY,
                'allowChoice' => false,
                'infoData' => [
                    'manual_text' => 'system text',
                ],
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $phone
     * @param string $email
     *
     * @return User|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createUserMock($name, $phone, $email)
    {
        $user = $this->getMockBuilder(User::class)
            ->setMethods(['getPhone', 'getEmail'])
            ->getMock();
        $user->method('getPhone')->willReturn($phone);
        $user->method('getEmail')->willReturn($email);
        $this->nameFormatter
            ->method('format')
            ->willReturnMap(
                [
                    [$user, null, $name]
                ]
            );

        return $user;
    }
}

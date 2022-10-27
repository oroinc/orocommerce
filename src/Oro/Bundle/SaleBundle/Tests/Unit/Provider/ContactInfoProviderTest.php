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
    private const DEFAULT_OPTIONS_MAP = [
        ContactInfoSourceOptionsProvider::DONT_DISPLAY => UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO,
        ContactInfoSourceOptionsProvider::PRE_CONFIGURED => ContactInfoUserOptionsProvider::USE_SYSTEM,
        ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER => UserOptionsProvider::USE_USER_PROFILE_DATA,
        ContactInfoSourceOptionsProvider::CUSTOMER_OWNER => UserOptionsProvider::USE_USER_PROFILE_DATA,
    ];

    /** @var NameFormatter */
    private $nameFormatter;

    /** @var ContactInfoProvider */
    private $contactInfoProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ContactInfoSourceOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contactSourceProvider;

    /** @var ContactInfoUserOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contactInfoUserOptionsProvider;

    /** @var ContactInfoFactory */
    private $contactInfoFactory;

    protected function setUp(): void
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

    /**
     * @dataProvider infoDataProvider
     */
    public function testFromCustomerOwner(
        ?CustomerUser $customerUser,
        string $displayOption,
        ?array $userSelectedOption,
        bool $allowChoice,
        array $infoData
    ) {
        $this->nameFormatter->expects(self::any())
            ->method('format')
            ->willReturnCallback(function (User $user) {
                return $user->getFullName();
            });

        $this->contactSourceProvider->expects(self::any())
            ->method('getSelectedOption')
            ->willReturn($displayOption);

        $this->contactInfoUserOptionsProvider->expects(self::any())
            ->method('getDefaultOption')
            ->willReturn(self::DEFAULT_OPTIONS_MAP[$displayOption]);

        if ($userSelectedOption) {
            $this->contactInfoUserOptionsProvider->expects(self::any())
                ->method('getSelectedOption')
                ->willReturnMap($userSelectedOption);
        } else {
            $this->contactInfoUserOptionsProvider->expects(self::never())
                ->method('getSelectedOption');
        }

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['oro_sale.allow_user_configuration', false, false, null, $allowChoice],
                [
                    'oro_sale.contact_info_manual_text',
                    false,
                    false,
                    $customerUser ? $customerUser->getOwner() : null,
                    'user text'
                ],
                [
                    'oro_sale.contact_info_manual_text',
                    false,
                    false,
                    $customerUser ? $customerUser->getCustomer()->getOwner() : null,
                    'user2 text'
                ],
                ['oro_sale.contact_details', false, false, null, 'system text'],
                ['oro_sale.guest_contact_info_text', false, false, null, 'text for anon'],
            ]);

        $contactInfo = $this->contactInfoProvider->getContactInfo($customerUser);

        $this->assertEquals($infoData, $contactInfo->all());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function infoDataProvider(): array
    {
        $user = $this->getUser('John Doe', '1111', 'mail@exemple.dev');
        $user2 = $this->getUser('Debra Morgan', '42', 'mail.debra@exemple.dev');
        $customerUser1 = $this->getCustomerUser($user);
        $customerUser2 = $this->getCustomerUser($user, $user2);

        return [
            'display=DONT_DISPLAY user_allow=false' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::DONT_DISPLAY,
                'userSelectedOption' => null,
                'allowChoice' => false,
                'infoData' => [],
            ],
            'display=DONT_DISPLAY user_allow=false anon' => [
                'customerUser' => null,
                'displayOption' => ContactInfoSourceOptionsProvider::DONT_DISPLAY,
                'userSelectedOption' => null,
                'allowChoice' => false,
                'infoData' => [],
            ],
            'display=DONT_DISPLAY user_display=ENTER_MANUALLY user_allow=true customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::DONT_DISPLAY,
                'userSelectedOption' => [[$user, UserOptionsProvider::ENTER_MANUALLY]],
                'allowChoice' => true,
                'infoData' => [
                    'manual_text' => 'user text',
                ],
            ],
            'display=CUSTOMER_OWNER user_display=DONT_DISPLAY user_allow=true customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_OWNER,
                'selectedOption' => [[$user, UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO]],
                'allowChoice' => true,
                'infoData' => [],
            ],
            'display=CUSTOMER_OWNER user_allow=false customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_OWNER,
                'selectedOption' => null,
                'allowChoice' => false,
                'infoData' => [
                    'email' => 'mail@exemple.dev',
                    'name' => 'John Doe',
                    'phone' => '1111',
                ],
            ],
            'display=CUSTOMER_OWNER user_display=DON_T_DISPLAY_CONTACT_INFO user_allow=true customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_OWNER,
                'selectedOption' => [[$user, UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO]],
                'allowChoice' => true,
                'infoData' => [],
            ],
            'display=CUSTOMER_OWNER user_display=ENTER_MANUALLY user_allow=true customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_OWNER,
                'selectedOption' => [[$user, UserOptionsProvider::ENTER_MANUALLY]],
                'allowChoice' => true,
                'infoData' => [
                    'manual_text' => 'user text',
                ],
            ],
            'display=CUSTOMER_USER_OWNER user_display=DONT_DISPLAY user_allow=true customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER,
                'selectedOption' => [[$user, UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO]],
                'allowChoice' => true,
                'infoData' => [],
            ],
            'display=CUSTOMER_USER_OWNER user_display=DONT_DISPLAY user_allow=true customerUser2' => [
                'customerUser' => $customerUser2,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER,
                'selectedOption' => [
                    [$user, ContactInfoUserOptionsProvider::USE_SYSTEM],
                    [$user2, UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO]
                ],
                'allowChoice' => true,
                'infoData' => [],
            ],
            'display=CUSTOMER_USER_OWNER user_allow=false customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER,
                'selectedOption' => null,
                'allowChoice' => false,
                'infoData' => [
                    'email' => 'mail@exemple.dev',
                    'name' => 'John Doe',
                    'phone' => '1111',
                ],
            ],
            'display=CUSTOMER_USER_OWNER user_display=DON_T_DISPLAY_CONTACT_INFO user_allow=true customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER,
                'selectedOption' => [[$user, UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO]],
                'allowChoice' => true,
                'infoData' => [],
            ],
            'display=CUSTOMER_USER_OWNER user_display=ENTER_MANUALLY user_allow=true customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER,
                'selectedOption' => [[$user, UserOptionsProvider::ENTER_MANUALLY]],
                'allowChoice' => true,
                'infoData' => [
                    'manual_text' => 'user text',
                ],
            ],
            'display=CUSTOMER_USER_OWNER user_display=ENTER_MANUALLY user_allow=true customerUser2' => [
                'customerUser' => $customerUser2,
                'displayOption' => ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER,
                'selectedOption' => [
                    [$user, ContactInfoUserOptionsProvider::USE_SYSTEM],
                    [$user2, UserOptionsProvider::ENTER_MANUALLY]
                ],
                'allowChoice' => true,
                'infoData' => [
                    'manual_text' => 'user2 text',
                ],
            ],
            'display=PRE_CONFIGURED user_display=ENTER_MANUALLY(1) user_allow=true customerUser2' => [
                'customerUser' => $customerUser2,
                'displayOption' => ContactInfoSourceOptionsProvider::PRE_CONFIGURED,
                'selectedOption' => [[$user, UserOptionsProvider::ENTER_MANUALLY]],
                'allowChoice' => true,
                'infoData' => [
                    'manual_text' => 'user text',
                ],
            ],
            'display=PRE_CONFIGURED user_display=ENTER_MANUALLY(2) user_allow=true customerUser2' => [
                'customerUser' => $customerUser2,
                'displayOption' => ContactInfoSourceOptionsProvider::PRE_CONFIGURED,
                'selectedOption' => [
                    [$user, ContactInfoUserOptionsProvider::USE_SYSTEM],
                    [$user2, UserOptionsProvider::ENTER_MANUALLY]
                ],
                'allowChoice' => true,
                'infoData' => [
                    'manual_text' => 'user2 text',
                ],
            ],
            'display=PRE_CONFIGURED user_display=USE_USER_PROFILE_DATA(2) user_allow=true customerUser2' => [
                'customerUser' => $customerUser2,
                'displayOption' => ContactInfoSourceOptionsProvider::PRE_CONFIGURED,
                'selectedOption' => [
                    [$user, ContactInfoUserOptionsProvider::USE_SYSTEM],
                    [$user2, UserOptionsProvider::USE_USER_PROFILE_DATA]
                ],
                'allowChoice' => true,
                'infoData' => [
                    'email' => 'mail.debra@exemple.dev',
                    'name' => 'Debra Morgan',
                    'phone' => '42'
                ],
            ],
            'display=PRE_CONFIGURED user_display=USE_SYSTEM user_allow=true customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::PRE_CONFIGURED,
                'selectedOption' => [[$user, ContactInfoUserOptionsProvider::USE_SYSTEM]],
                'allowChoice' => true,
                'infoData' => [
                    'manual_text' => 'system text',
                ],
            ],
            'display=PRE_CONFIGURED user_allow=false customerUser1' => [
                'customerUser' => $customerUser1,
                'displayOption' => ContactInfoSourceOptionsProvider::PRE_CONFIGURED,
                'selectedOption' => null,
                'allowChoice' => false,
                'infoData' => [
                    'manual_text' => 'system text',
                ],
            ],
            'display=PRE_CONFIGURED user_allow=false' => [
                'customerUser' => null,
                'displayOption' => ContactInfoSourceOptionsProvider::PRE_CONFIGURED,
                'selectedOption' => null,
                'allowChoice' => false,
                'infoData' => [
                    'manual_text' => 'text for anon',
                ],
            ],
        ];
    }

    private function getCustomerUser(User $customerUserOwner, User $customerOwner = null): CustomerUser
    {
        $customer = new Customer();
        $customer->setOwner($customerOwner ?: $customerUserOwner);
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);
        $customerUser->setOwner($customerUserOwner);

        return $customerUser;
    }

    private function getUser(string $name, string $phone, string $email): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getEmail', 'getFullName'])
            ->addMethods(['getPhone'])
            ->getMock();
        $user->expects(self::any())
            ->method('getPhone')
            ->willReturn($phone);
        $user->expects(self::any())
            ->method('getEmail')
            ->willReturn($email);
        $user->expects(self::any())
            ->method('getFullName')
            ->willReturn($name);

        return $user;
    }
}

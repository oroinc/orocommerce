<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CheckoutCustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Handler\SaveConsentAcceptanceHandler;
use Oro\Bundle\ConsentBundle\Storage\CustomerConsentAcceptancesStorageInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CheckoutCustomerConsentsEventSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var SaveConsentAcceptanceHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $saveConsentAcceptanceHandler;

    /** @var CustomerConsentAcceptancesStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var CheckoutCustomerConsentsEventSubscriber */
    private $subscriber;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mainForm;

    /** @var CustomerUserExtractor */
    protected $customerUserExtractor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->saveConsentAcceptanceHandler = $this->createMock(SaveConsentAcceptanceHandler::class);
        $this->storage = $this->createMock(CustomerConsentAcceptancesStorageInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->customerUserExtractor = new CustomerUserExtractor();
        $this->customerUserExtractor->addMapping(Checkout::class, 'customerUser');
        $this->customerUserExtractor->addMapping(Checkout::class, 'registeredCustomerUser');

        $this->subscriber = new CheckoutCustomerConsentsEventSubscriber(
            $this->saveConsentAcceptanceHandler,
            $this->storage,
            $this->tokenStorage,
            $this->customerUserExtractor
        );
        $this->mainForm = $this->createMock(FormInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->saveConsentAcceptanceHandler);
        unset($this->storage);
        unset($this->tokenStorage);
        unset($this->subscriber);
    }

    public function testSaveConsentAcceptancesWithInvalidData()
    {
        $this->mainForm
            ->expects($this->any())
            ->method('has')
            ->with('customerConsents')
            ->willReturn(true);

        $this->mainForm
            ->expects($this->any())
            ->method('isValid')
            ->willReturn(false);

        $this->mainForm
            ->expects($this->never())
            ->method('get')
            ->with('customerConsents');

        $this->subscriber->saveConsentAcceptances(new FormEvent($this->mainForm, null));
    }

    /**
     * @dataProvider saveConsentAcceptancesWithValidDataProvider
     *
     * @param mixed $customerConsentsData
     * @param bool|Checkout $checkout
     * @param bool|CustomerUser $customerUser
     * @param AnonymousCustomerUserToken|TokenInterface $token
     * @param bool $expectedSaveHandlerCalled
     * @param bool $expectedStorageCalled
     */
    public function testSaveConsentAcceptancesWithValidData(
        $customerConsentsData,
        $checkout,
        $customerUser,
        $token,
        bool $expectedSaveHandlerCalled,
        bool $expectedStorageCalled
    ) {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $customerConsentsFieldConfig = $this->createMock(FormConfigInterface::class);
        $customerConsentsFieldConfig
            ->expects($this->any())
            ->method('getOption')
            ->with('checkout')
            ->willReturn($checkout);

        $customerConsentsField = $this->createMock(FormInterface::class);
        $customerConsentsField
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($customerConsentsFieldConfig);

        $customerConsentsField
            ->expects($this->any())
            ->method('getData')
            ->willReturn($customerConsentsData);

        $this->mainForm
            ->expects($this->any())
            ->method('has')
            ->with('customerConsents')
            ->willReturn(true);

        $this->mainForm
            ->expects($this->any())
            ->method('isValid')
            ->willReturn(true);

        $this->mainForm
            ->expects($this->any())
            ->method('get')
            ->with('customerConsents')
            ->willReturn($customerConsentsField);

        if ($expectedSaveHandlerCalled) {
            $this->saveConsentAcceptanceHandler
                ->expects($this->once())
                ->method('save')
                ->with($customerUser, $customerConsentsData);
        } else {
            $this->saveConsentAcceptanceHandler
                ->expects($this->never())
                ->method('save');
        }

        if ($expectedStorageCalled) {
            $this->storage
                ->expects($this->once())
                ->method('saveData')
                ->with($customerConsentsData);
        } else {
            $this->storage
                ->expects($this->never())
                ->method('saveData');
        }

        $this->subscriber->saveConsentAcceptances(new FormEvent($this->mainForm, []));
    }

    public function saveConsentAcceptancesWithValidDataProvider()
    {
        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);

        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $checkout = $this->getEntity(Checkout::class, [
            'id' => 1,
            'customerUser' => $customerUser
        ]);

        /**
         * @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token
         */
        $customerUserToken = $this->createMock(TokenInterface::class);
        $anonymousToken = new AnonymousCustomerUserToken('');

        return [
            "Field 'ConsentAcceptances' contains invalid data" => [
                'customerConsentsData' => '',
                'checkout' => false,
                'customerUser' => false,
                'token' => $anonymousToken,
                'expectedSaveHandlerCalled' => false,
                'expectedStorageCalled' => false,
            ],
            "Field 'ConsentAcceptances' contains empty array data" => [
                'customerConsentsData' => [],
                'checkout' => false,
                'customerUser' => false,
                'token' => $anonymousToken,
                'expectedSaveHandlerCalled' => false,
                'expectedStorageCalled' => false,
            ],
            "Field 'ConsentAcceptances' contains correct data and customerUser present in the config" => [
                'customerConsentsData' => [$consentAcceptance],
                'checkout' => $checkout,
                'customerUser' => $customerUser,
                'token' => $customerUserToken,
                'expectedSaveHandlerCalled' => true,
                'expectedStorageCalled' => false,
            ],
            "Field 'ConsentAcceptances' contains correct data, but customerUser isn't present in the config" => [
                'customerConsentsData' => [$consentAcceptance],
                'checkout' => null,
                'customerUser' => $customerUser,
                'token' => $anonymousToken,
                'expectedSaveHandlerCalled' => false,
                'expectedStorageCalled' => true,
            ],
        ];
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::POST_SUBMIT => ['saveConsentAcceptances', -10]],
            CheckoutCustomerConsentsEventSubscriber::getSubscribedEvents()
        );
    }
}

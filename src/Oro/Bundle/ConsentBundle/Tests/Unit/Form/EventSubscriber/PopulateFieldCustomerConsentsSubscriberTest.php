<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\PopulateFieldCustomerConsentsSubscriber;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class PopulateFieldCustomerConsentsSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentAcceptanceProvider;

    /**
     * @var PopulateFieldCustomerConsentsSubscriber
     */
    private $subscriber;

    /**
     * @var CustomerUserExtractor
     */
    private $customerUserExtractor;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entity) {
                if (is_object($entity)) {
                    return get_class($entity);
                }

                return null;
            });
        $this->customerUserExtractor = new CustomerUserExtractor($this->doctrineHelper);

        $this->subscriber = new PopulateFieldCustomerConsentsSubscriber(
            $this->consentAcceptanceProvider,
            $this->customerUserExtractor
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->consentAcceptanceProvider);
        unset($this->subscriber);
    }

    /**
     * @dataProvider populateFieldProvider
     *
     * @param bool       $hasCustomerConsentsField
     * @param object|null $eventData
     * @param bool       $isFieldPopulateAllowed
     * @param array      $customerUserMappings
     */
    public function testPopulateField(
        bool $hasCustomerConsentsField,
        $eventData,
        bool $isFieldPopulateAllowed,
        array $customerUserMappings
    ) {
        array_walk($customerUserMappings, function ($propertyPath, $className) {
            $this->customerUserExtractor->addMapping($className, $propertyPath);
        });

        /**
         * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form
         */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn($hasCustomerConsentsField);

        if ($isFieldPopulateAllowed) {
            $consentAcceptance = 'consentAcceptances';

            $this->consentAcceptanceProvider
                ->expects($this->once())
                ->method('getCustomerConsentAcceptances')
                ->willReturn($consentAcceptance);

            $customerConsentsField = $this->createMock(FormInterface::class);
            $customerConsentsField
                ->expects($this->once())
                ->method('setData')
                ->with($consentAcceptance);

            $form
                ->expects($this->once())
                ->method('get')
                ->with(CustomerConsentsType::TARGET_FIELDNAME)
                ->willReturn($customerConsentsField);
        } else {
            $form
                ->expects($this->never())
                ->method('get');

            $this->consentAcceptanceProvider
                ->expects($this->never())
                ->method('getCustomerConsentAcceptances');
        }

        $formEvent = new FormEvent($form, $eventData);
        $this->subscriber->populateField($formEvent);
    }

    /**
     * @return array
     */
    public function populateFieldProvider()
    {
        return [
            'Has no customer consent field' => [
                'hasCustomerConsentsField' => false,
                'eventData' => $this->getEntity(CustomerUser::class, ['id' => 1]),
                'isFieldPopulateAllowed' => false,
                'customerUserMappings' => []
            ],
            'Not applicable event data' => [
                'hasCustomerConsentsField' => true,
                'eventData' => null,
                'isFieldPopulateAllowed' => false,
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ]
            ],
            'Field populates with prepared data' => [
                'hasCustomerConsentsField' => true,
                'eventData' => $this->getEntity(CustomerUser::class, ['id' => 1]),
                'isFieldPopulateAllowed' => true,
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ]
            ],
            'Event data with Checkout entity' => [
                'hasCustomerConsentsField' => true,
                'eventData' => $this->getEntity(Checkout::class, [
                    'id' => 9,
                    'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 1])
                ]),
                'isFieldPopulateAllowed' => true,
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ]
            ],
            'Event data with RFQ entity' => [
                'hasCustomerConsentsField' => true,
                'eventData' => $this->getEntity(Request::class, [
                    'id' => 10,
                    'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 1])
                ]),
                'isFieldPopulateAllowed' => true,
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ]
            ],
            'Event data with not supported entity' => [
                'hasCustomerConsentsField' => true,
                'eventData' => $this->getEntity(RequestProduct::class, [
                    'id' => 11,
                    'request' => $this->getEntity(Request::class, [
                        'id' => 10,
                        'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 1])
                    ])
                ]),
                'isFieldPopulateAllowed' => false,
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ]
            ]
        ];
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::POST_SET_DATA => 'populateField'],
            PopulateFieldCustomerConsentsSubscriber::getSubscribedEvents()
        );
    }
}

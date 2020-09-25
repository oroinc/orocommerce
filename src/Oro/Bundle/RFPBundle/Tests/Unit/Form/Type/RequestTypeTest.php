<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserMultiSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\RequestType;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /**
     * @var RequestType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->formType = new RequestType();
        $this->formType->setDataClass('Oro\Bundle\RFPBundle\Entity\Request');

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit\Framework\MockObject\MockObject|OptionsResolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Oro\Bundle\RFPBundle\Entity\Request',
                    'csrf_token_id'  => 'rfp_request',
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider()
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', $this->createPrice(20, 'USD'));
        $requestProduct     = $this->getRequestProduct(2, 'comment_stripped', [$requestProductItem]);

        $longStr    = str_repeat('a', 256);
        $longEmail  = $longStr . '@example.com';
        $email      = 'test@example.com';
        $date       = '2015-10-15';
        $dateObj    = new \DateTime($date . 'T00:00:00+0000');

        return [
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName'     => 'FirstName',
                    'lastName'      => 'LastName',
                    'email'         => $email,
                    'note'          => 'note',
                    'poNumber'      => 'poNumber',
                    'shipUntil'     => $date,
                    'role'          => 'role',
                    'phone'         => '123',
                    'company'       => 'company',
                    'requestProducts' => [
                        [
                            'product'   => 2,
                            'comment'   => 'comment',
                            'requestProductItems' => [
                                [
                                    'quantity' => 10,
                                    'productUnit' => 'kg',
                                    'price' => ['value' => 20, 'currency' => 'USD',],
                                ],
                            ],
                        ],
                    ],
                    'assignedUsers' => [1],
                    'assignedCustomerUsers' => [11],
                ],
                'expectedData'  => $this->getRequest(
                    'FirstName',
                    'LastName',
                    $email,
                    'note',
                    'company',
                    'role',
                    'poNumber',
                    $dateObj,
                    '123'
                )
                    ->addRequestProduct($requestProduct)
                    ->addAssignedUser($this->getUser(1))
                    ->addAssignedCustomerUser($this->getCustomerUser(11)),
                'defaultData'   => $this->getRequest(
                    'FirstName',
                    'LastName',
                    $email,
                    'note',
                    'company',
                    'role',
                    'poNumber',
                    $dateObj,
                    '123'
                ),
            ],
            'valid data empty items' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName'     => 'FirstName',
                    'lastName'      => 'LastName',
                    'email'         => $email,
                    'note'          => 'note',
                    'poNumber'      => 'poNumber',
                    'shipUntil'     => $date,
                    'role'          => 'role',
                    'phone'         => '123',
                    'company'       => 'company',
                ],
                'expectedData'  => $this->getRequest(
                    'FirstName',
                    'LastName',
                    $email,
                    'note',
                    'company',
                    'role',
                    'poNumber',
                    $dateObj,
                    '123'
                ),
                'defaultData'   => $this->getRequest(
                    'FirstName',
                    'LastName',
                    $email,
                    'note',
                    'company',
                    'role',
                    'poNumber',
                    $dateObj,
                    '123'
                ),
            ],
            'empty first name' => [
                'isValid'       => false,
                'submittedData' => [
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'note'      => 'note',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest(null, 'LastName', $email, 'note', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'first name len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => $longStr,
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'note'      => 'note',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest($longStr, 'LastName', $email, 'note', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'empty last name' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'email'     => $email,
                    'note'      => 'note',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', null, $email, 'note', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'last name len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => $longStr,
                    'email'     => $email,
                    'note'      => 'note',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', $longStr, $email, 'note', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'empty email' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'note'      => 'note',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', null, 'note', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'invalid email' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => 'no-email',
                    'note'      => 'note',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', 'no-email', 'note', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'email len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $longEmail,
                    'note'      => 'note',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $longEmail, 'note', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'empty note' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, null, 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'company len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'note'      => 'note',
                    'role'      => 'role',
                    'company'   => $longStr,
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'note', $longStr, 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'role len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'note'      => 'note',
                    'role'      => $longStr,
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'note', 'company', $longStr),
                'defaultData'   => $this->getRequest(),
            ],
            'poNumber len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'note'      => 'note',
                    'poNumber'  => $longStr,
                    'shipUntil' => $date,
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest(
                    'FirstName',
                    'LastName',
                    $email,
                    'note',
                    'company',
                    'role',
                    $longStr,
                    $dateObj
                ),
                'defaultData'   => $this->getRequest(),
            ],
            'invalid shipUntil' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'note'      => 'note',
                    'poNumber'  => 'poNumber',
                    'shipUntil' => 'no-date',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest(
                    'FirstName',
                    'LastName',
                    $email,
                    'note',
                    'company',
                    'role',
                    'poNumber',
                    null
                ),
                'defaultData'   => $this->getRequest(),
            ],
            'valid data empty PO number' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName'     => 'FirstName',
                    'lastName'      => 'LastName',
                    'email'         => $email,
                    'note'          => 'note',
                    'poNumber'      => null,
                    'shipUntil'     => null,
                    'role'          => 'role',
                    'phone'         => '123',
                    'company'       => 'company',
                ],
                'expectedData'  => $this->getRequest(
                    'FirstName',
                    'LastName',
                    $email,
                    'note',
                    'company',
                    'role',
                    null,
                    null,
                    '123'
                ),
                'defaultData'   => $this->getRequest(),
            ],
        ];
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $note
     * @param string $company
     * @param string $role
     * @param string $poNumber
     * @param \DateTime $shipUntil
     * @param string $phone
     * @return Request
     */
    protected function getRequest(
        $firstName = null,
        $lastName = null,
        $email = null,
        $note = null,
        $company = null,
        $role = null,
        $poNumber = null,
        $shipUntil = null,
        $phone = null
    ) {
        $request = new Request();

        $request
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setNote($note)
            ->setCompany($company)
            ->setRole($role)
            ->setPhone($phone)
            ->setPoNumber($poNumber)
            ->setShipUntil($shipUntil)
        ;

        return $request;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $productSelectType          = $this->prepareProductSelectType();
        $userMultiSelectType        = $this->prepareUserMultiSelectType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();
        $customerMultiSelectType     = $this->prepareCustomerUserMultiSelectType();

        $customerSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', 1),
                2 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', 2),
            ],
            CustomerSelectType::NAME
        );

        $customerUserSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', 1),
                2 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', 2),
            ],
            CustomerUserSelectType::NAME
        );

        $requestProductType = new RequestProductType();
        $requestProductType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    PriceType::class                   => $priceType,
                    ProductSelectType::class           => $productSelectType,
                    CustomerSelectType::class          => $customerSelectType,
                    RequestProductType::class          => $requestProductType,
                    UserMultiSelectType::class         => $userMultiSelectType,
                    CustomerUserSelectType::class      => $customerUserSelectType,
                    CurrencySelectionType::class       => $currencySelectionType,
                    RequestProductItemType::class      => $requestProductItemType,
                    ProductUnitSelectionType::class    => $productUnitSelectionType,
                    CustomerUserMultiSelectType::class => $customerMultiSelectType,
                    QuantityType::class                => $this->getQuantityType(),
                ],
                [FormType::class => [new StripTagsExtensionStub($this)]]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @return array
     */
    protected function getValidators()
    {
        $quantityUnitPrecision = new QuantityUnitPrecision();
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($quantity) {
                return (float)$quantity;
            });
        $quantityUnitPrecisionValidator = new QuantityUnitPrecisionValidator($roundingService);

        return [
            $quantityUnitPrecision->validatedBy() => $quantityUnitPrecisionValidator,
        ];
    }
}

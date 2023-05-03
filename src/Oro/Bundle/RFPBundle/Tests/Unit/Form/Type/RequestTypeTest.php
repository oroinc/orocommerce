<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserMultiSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\RequestType;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /** @var RequestType */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = new RequestType();
        $this->formType->setDataClass(Request::class);

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => Request::class, 'csrf_token_id' => 'rfp_request']);

        $this->formType->configureOptions($resolver);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider(): array
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', Price::create(20, 'USD'));
        $requestProduct = $this->getRequestProduct(2, 'comment_stripped', [$requestProductItem]);

        $longStr = str_repeat('a', 256);
        $longEmail = $longStr . '@example.com';
        $email = 'test@example.com';
        $date = '2015-10-15';
        $dateObj = new \DateTime($date . 'T00:00:00+0000');

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
                    '123',
                    'poNumber',
                    $dateObj
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
                    '123',
                    'poNumber',
                    $dateObj
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
                    '123',
                    'poNumber',
                    $dateObj
                ),
                'defaultData'   => $this->getRequest(
                    'FirstName',
                    'LastName',
                    $email,
                    'note',
                    'company',
                    'role',
                    '123',
                    'poNumber',
                    $dateObj
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
                    null,
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
                    null,
                    'poNumber'
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
                    '123'
                ),
                'defaultData'   => $this->getRequest(),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $requestProductType = new RequestProductType();
        $requestProductType->setDataClass(RequestProduct::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $this->preparePriceType(),
                    ProductSelectType::class => $this->prepareProductSelectType(),
                    CustomerSelectType::class => new EntityTypeStub([
                        1 => $this->getEntity(Customer::class, 1),
                        2 => $this->getEntity(Customer::class, 2),
                    ]),
                    $requestProductType,
                    UserMultiSelectType::class => $this->prepareUserMultiSelectType(),
                    CustomerUserSelectType::class => new EntityTypeStub([
                        1 => $this->getEntity(CustomerUser::class, 1),
                        2 => $this->getEntity(CustomerUser::class, 2),
                    ]),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    RequestProductItemType::class => $this->prepareRequestProductItemType(),
                    ProductUnitSelectionType::class => $this->prepareProductUnitSelectionType(),
                    CustomerUserMultiSelectType::class => $this->prepareCustomerUserMultiSelectType(),
                    $this->getQuantityType(),
                ],
                [FormType::class => [new StripTagsExtensionStub($this)]]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($quantity) {
                return (float)$quantity;
            });

        return [
            'oro_product_quantity_unit_precision' => new QuantityUnitPrecisionValidator($roundingService),
        ];
    }
}

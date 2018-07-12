<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\Frontend;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Form\Type\Frontend\CustomerUserMultiSelectType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType as FrontendRequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\AbstractTest;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    const DATA_CLASS = 'Oro\Bundle\RFPBundle\Entity\Request';

    /**
     * @var RequestType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new RequestType();
        $this->formType->setDataClass(self::DATA_CLASS);

        parent::setUp();
    }

    /**
     * Test configureOptions
     */
    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');

        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => self::DATA_CLASS
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider()
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', $this->createPrice(20, 'USD'));
        $requestProduct     = $this->getRequestProduct(2, 'comment', [$requestProductItem]);

        $email      = 'test@example.com';
        $date       = '2015-10-15';
        $dateObj    = new \DateTime($date . 'T00:00:00+0000');

        return [
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'phone'     => '+38 (044) 247-68-00',
                    'company'   => 'company',
                    'role'      => 'role',
                    'note'      => 'note',
                    'poNumber'  => 'poNumber',
                    'shipUntil' => $date,
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
                    'assignedCustomerUsers' => [10],
                ],
                'expectedData'  => $this
                    ->getRequest(
                        'FirstName',
                        'LastName',
                        $email,
                        'note',
                        'company',
                        'role',
                        '+38 (044) 247-68-00',
                        'poNumber',
                        $dateObj
                    )
                    ->addRequestProduct($requestProduct)
                    ->addAssignedCustomerUser($this->getCustomerUser(10)),
                'defaultData'  => $this
                    ->getRequest(
                        'FirstName',
                        'LastName',
                        $email,
                        'note',
                        'company',
                        'role',
                        '+38 (044) 247-68-00',
                        'poNumber',
                        $dateObj
                    )
                    ->addRequestProduct($requestProduct),
            ],
            'empty PO number' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'phone'     => '+38 (044) 247-68-00',
                    'company'   => 'company',
                    'role'      => 'role',
                    'note'      => 'note',
                    'poNumber'  => null,
                    'shipUntil' => null,
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
                ],
                'expectedData'  => $this
                    ->getRequest(
                        'FirstName',
                        'LastName',
                        $email,
                        'note',
                        'company',
                        'role',
                        '+38 (044) 247-68-00',
                        null,
                        null
                    )
                    ->addRequestProduct($requestProduct),
                'defaultData'  => $this
                    ->getRequest(
                        'FirstName',
                        'LastName',
                        $email,
                        'note',
                        'company',
                        'role',
                        '+38 (044) 247-68-00',
                        null,
                        null
                    )
                    ->addRequestProduct($requestProduct),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /* @var $productUnitLabelFormatter UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
        $productUnitLabelFormatter = $this->getMockBuilder(
            UnitLabelFormatterInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductSelectType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();
        $customerUserMultiSelectType = $this->prepareCustomerUserMultiSelectType();
        $requestProductType         = new RequestProductType($productUnitLabelFormatter);
        $requestProductType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');
        $frontendRequestProductType = new FrontendRequestProductType();
        $frontendRequestProductType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    ProductUnitSelectionType::class    => new ProductUnitSelectionTypeStub(),
                    ProductSelectType::class           => new ProductSelectTypeStub(),
                    PriceType::class                   => $priceType,
                    ProductSelectType::class           => $entityType,
                    RequestProductType::class          => $requestProductType,
                    CurrencySelectionType::class       => $currencySelectionType,
                    RequestProductItemType::class      => $requestProductItemType,
                    ProductUnitSelectionType::class    => $productUnitSelectionType,
                    CustomerUserMultiSelectType::class => $customerUserMultiSelectType,
                    FrontendRequestProductType::class  => $frontendRequestProductType,
                    QuantityType::class                => $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @return EntityType
     */
    protected function prepareCustomerUserMultiSelectType()
    {
        return new EntityType(
            [
                10 => $this->getCustomerUser(10),
                11 => $this->getCustomerUser(11),
            ],
            CustomerUserMultiSelectType::NAME,
            [
                'multiple' => true
            ]
        );
    }
}

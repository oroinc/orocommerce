<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\Frontend;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Form\Type\Frontend\CustomerUserMultiSelectType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType as FrontendRequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\AbstractTest;
use Oro\Component\Testing\Unit\PreloadedExtension;
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
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => Request::class]);

        $this->formType->configureOptions($resolver);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider(): array
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', Price::create(20, 'USD'));
        $requestProduct = $this->getRequestProduct(2, 'comment', [$requestProductItem]);

        $email = 'test@example.com';
        $date = '2015-10-15';
        $dateObj = new \DateTime($date . 'T00:00:00+0000');

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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $requestProductType = new RequestProductType();
        $requestProductType->setDataClass(RequestProduct::class);

        $frontendRequestProductType = new FrontendRequestProductType();
        $frontendRequestProductType->setDataClass(RequestProduct::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $this->preparePriceType(),
                    ProductSelectType::class => $this->prepareProductSelectType(),
                    $requestProductType,
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    RequestProductItemType::class => $this->prepareRequestProductItemType(),
                    ProductUnitSelectionType::class => $this->prepareProductUnitSelectionType(),
                    CustomerUserMultiSelectType::class => $this->prepareCustomerUserMultiSelectType(),
                    $frontendRequestProductType,
                    $this->getQuantityType(),
                ],
                []
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

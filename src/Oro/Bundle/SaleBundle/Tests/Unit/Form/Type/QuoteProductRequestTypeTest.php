<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteProductRequestTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /** @var QuoteProductRequestType */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = new QuoteProductRequestType();
        $this->formType->setDataClass(QuoteProductRequest::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('compact_units', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);

                return true;
            }));

        $this->formType->configureOptions($resolver);
    }

    /**
     * {@inheritDoc}
     */
    public function submitProvider(): array
    {
        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getQuoteProductRequest(1),
                'defaultData'   => $this->getQuoteProductRequest(1),
            ],
            'empty quote product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductRequest(2, 10, 'kg', Price::create(20, 'EUR'))
                    ->setQuoteProduct(null),
                'defaultData'   => $this->getQuoteProductRequest(2)->setQuoteProduct(null),
            ],
            'empty quantity' => [
                'isValid'       => true,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 11,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductRequest(2, null, 'kg', Price::create(11, 'EUR')),
                'defaultData'   => $this->getQuoteProductRequest(2),
            ],
            'empty product unit' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 22,
                    'price'         => [
                        'value'     => 33,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductRequest(3, 22, null, Price::create(33, 'EUR')),
                'defaultData'   => $this->getQuoteProductRequest(3),
            ],
            'empty price' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 44,
                    'productUnit'   => 'kg',
                ],
                'expectedData'  => $this->getQuoteProductRequest(2, 44, 'kg'),
                'defaultData'   => $this->getQuoteProductRequest(2),
            ],
            'empty request product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 88,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 99,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductRequest(5, 88, 'kg', Price::create(99, 'EUR'))
                    ->setQuoteProduct(null),
                'defaultData'   => $this->getQuoteProductRequest(5)
                    ->setQuoteProduct(null),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 11,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 22,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductRequest(5, 11, 'kg', Price::create(22, 'EUR')),
                'defaultData'   => $this->getQuoteProductRequest(5),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    $priceType,
                    ProductUnitSelectionType::class => $this->prepareProductUnitSelectionType(),
                    $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}

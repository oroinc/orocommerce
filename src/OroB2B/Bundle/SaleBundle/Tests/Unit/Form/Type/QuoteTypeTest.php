<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityType as CustomerEntityType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductCollectionType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferCollectionType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductRequestCollectionType;

class QuoteTypeTest extends AbstractTest
{
    /**
     * @var QuoteType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new QuoteType();
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\Quote',
                    'intention'     => 'sale_quote',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteType::NAME, $this->formType->getName());
    }

    /**
     * @param int $ownerId
     * @param int $accountUserId
     * @param int $accountId
     * @param QuoteProduct[] $items
     * @return Quote
     */
    protected function getQuote($ownerId, $accountUserId = null, $accountId = null, array $items = [])
    {
        $quote = new Quote();
        $quote->setOwner($this->getEntity('Oro\Bundle\UserBundle\Entity\User', $ownerId));

        if (null !== $accountUserId) {
            $quote->setAccountUser($this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\AccountUser', $accountUserId));
        }

        if (null !== $accountId) {
            $quote->setAccount($this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', $accountId));
        }

        foreach ($items as $item) {
            $quote->addQuoteProduct($item);
        }

        return $quote;
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $quoteProductOffer = $this->getQuoteProductOffer(2, 33, 'kg', self::QPO_PRICE_TYPE1, Price::create(44, 'USD'));
        $quoteProduct = $this->getQuoteProduct(2, self::QP_TYPE1, 'comment1', 'comment2', [], [$quoteProductOffer]);

        return [
            'empty owner' => [
                'isValid'       => false,
                'submittedData' => [
                ],
                'expectedData'  => new Quote(),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'owner' => 1,
                    'accountUser' => 1,
                    'account' => 2,
                    'quoteProducts' => [
                        [
                            'product'   => 2,
                            'type'      => self::QP_TYPE1,
                            'comment'   => 'comment1',
                            'commentCustomer' => 'comment2',
                            'quoteProductOffers' => [
                                [
                                    'quantity'      => 33,
                                    'productUnit'   => 'kg',
                                    'priceType'     => self::QPO_PRICE_TYPE1,
                                    'price'         => [
                                        'value'     => 44,
                                        'currency'  => 'USD',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $this->getQuote(1, 1, 2, [$quoteProduct]),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        /* @var $productUnitLabelFormatter \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $userSelectType = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 1),
                2 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 2),
            ],
            'oro_user_select'
        );

        $customerSelectType = new CustomerEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1),
                2 => $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 2),
            ],
            CustomerSelectType::NAME
        );

        $accountUserSelectType = new CustomerEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\AccountUser', 1),
                2 => $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\AccountUser', 2),
            ],
            AccountUserSelectType::NAME
        );

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $productSelectType          = new ProductSelectTypeStub();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    OroDateTimeType::NAME                       => new OroDateTimeType(),
                    QuoteProductType::NAME                      => new QuoteProductType(
                        $translator,
                        $productUnitLabelFormatter,
                        $this->quoteProductTypeFormatter
                    ),
                    CollectionType::NAME                        => new CollectionType(),
                    QuoteProductOfferType::NAME                 => new QuoteProductOfferType(
                        $translator,
                        $this->quoteProductOfferTypeFormatter
                    ),
                    QuoteProductCollectionType::NAME            => new QuoteProductCollectionType(),
                    QuoteProductOfferCollectionType::NAME       => new QuoteProductOfferCollectionType(),
                    QuoteProductRequestCollectionType::NAME     => new QuoteProductRequestCollectionType(),
                    $priceType->getName()                       => $priceType,
                    $entityType->getName()                      => $entityType,
                    $userSelectType->getName()                  => $userSelectType,
                    $productSelectType->getName()               => $productSelectType,
                    $currencySelectionType->getName()           => $currencySelectionType,
                    $productUnitSelectionType->getName()        => $productUnitSelectionType,
                    $customerSelectType->getName()              => $customerSelectType,
                    $accountUserSelectType->getName()           => $accountUserSelectType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}

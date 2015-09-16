<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductCollectionType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferCollectionType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductRequestCollectionType;
use OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type\Stub\EntityType as StubEntityType;

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
        $this->formType->setDataClass('OroB2B\Bundle\SaleBundle\Entity\Quote');
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
     * @param bool $locked
     * @return Quote
     */
    protected function getQuote($ownerId, $accountUserId = null, $accountId = null, array $items = [], $locked = false)
    {
        $quote = new Quote();
        $quote->setOwner($this->getEntity('Oro\Bundle\UserBundle\Entity\User', $ownerId));

        if (null !== $accountUserId) {
            $quote->setAccountUser($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $accountUserId));
        }

        if (null !== $accountId) {
            $quote->setAccount($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $accountId));
        }

        foreach ($items as $item) {
            $quote->addQuoteProduct($item);
        }
        $quote->setLocked($locked);

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
                    'locked' => false,
                    'quoteProducts' => [
                        [
                            'product'   => 2,
                            'type'      => self::QP_TYPE1,
                            'comment'   => 'comment1',
                            'commentAccount' => 'comment2',
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
                'expectedData'  => $this->getQuote(1, 1, 2, [$quoteProduct], false),
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

        /* @var $registry ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        /* @var $productUnitLabelFormatter \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $userSelectType = new StubEntityType(
            [
                1 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 1),
                2 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 2),
            ],
            'oro_user_select'
        );

        $accountSelectType = new StubEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
            ],
            AccountSelectType::NAME
        );

        $accountUserSelectType = new StubEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', 2),
            ],
            AccountUserSelectType::NAME
        );

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $productSelectType          = new ProductSelectTypeStub();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();
        $quoteProductOfferType      = $this->prepareQuoteProductOfferType();
        $quoteProductRequestType    = $this->prepareQuoteProductRequestType();

        $quoteProductType = new QuoteProductType(
            $translator,
            $productUnitLabelFormatter,
            $this->quoteProductFormatter,
            $registry
        );
        $quoteProductType->setDataClass('OroB2B\Bundle\SaleBundle\Entity\QuoteProduct');

        return [
            new PreloadedExtension(
                [
                    OroDateTimeType::NAME                       => new OroDateTimeType(),
                    CollectionType::NAME                        => new CollectionType(),
                    QuoteProductOfferType::NAME                 => new QuoteProductOfferType(
                        $this->quoteProductOfferFormatter
                    ),
                    QuoteProductCollectionType::NAME            => new QuoteProductCollectionType(),
                    QuoteProductOfferCollectionType::NAME       => new QuoteProductOfferCollectionType(),
                    QuoteProductRequestCollectionType::NAME     => new QuoteProductRequestCollectionType(),
                    ProductRemovedSelectType::NAME              => new StubProductRemovedSelectType(),
                    ProductUnitRemovedSelectionType::NAME       => new StubProductUnitRemovedSelectionType(),
                    $priceType->getName()                       => $priceType,
                    $entityType->getName()                      => $entityType,
                    $userSelectType->getName()                  => $userSelectType,
                    $quoteProductType->getName()                => $quoteProductType,
                    $productSelectType->getName()               => $productSelectType,
                    $currencySelectionType->getName()           => $currencySelectionType,
                    $quoteProductOfferType->getName()           => $quoteProductOfferType,
                    $quoteProductRequestType->getName()         => $quoteProductRequestType,
                    $productUnitSelectionType->getName()        => $productUnitSelectionType,
                    $accountSelectType->getName()               => $accountSelectType,
                    $accountUserSelectType->getName()           => $accountUserSelectType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}

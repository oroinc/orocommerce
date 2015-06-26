<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductItemType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductCollectionType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductItemCollectionType;

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

        $userSelectType = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 1),
                2 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 2),
            ],
            'oro_user_select'
        );

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $productSelectType          = new ProductSelectTypeStub();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    OroDateTimeType::NAME                   => new OroDateTimeType(),
                    QuoteProductType::NAME                  => new QuoteProductType($translator),
                    CollectionType::NAME                    => new CollectionType(),
                    QuoteProductItemType::NAME              => new QuoteProductItemType($translator),
                    QuoteProductCollectionType::NAME        => new QuoteProductCollectionType(),
                    QuoteProductItemCollectionType::NAME    => new QuoteProductItemCollectionType(),
                    $priceType->getName()                   => $priceType,
                    $entityType->getName()                  => $entityType,
                    $userSelectType->getName()              => $userSelectType,
                    $productSelectType->getName()           => $productSelectType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
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
     * @param QuoteProduct[] $items
     * @return Quote
     */
    protected function getQuote($ownerId, array $items = [])
    {
        $quote = new Quote();
        $quote->setOwner($this->getEntity('Oro\Bundle\UserBundle\Entity\User', $ownerId));

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
        $quoteProductItem   = $this->getQuoteProductItem(10, 'kg', Price::create(20, 'USD'));
        $quoteProduct       = $this->getQuoteProduct(2, [$quoteProductItem]);
        $quote              = $this->getQuote(1, [$quoteProduct]);

        return [
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'owner' => 1,
                    'quoteProducts' => [
                        [
                            'product' => 2,
                            'quoteProductItems' => [
                                [
                                    'quantity'      => 10,
                                    'productUnit'   => 'kg',
                                    'price'         => [
                                        'value'     => 20,
                                        'currency'  => 'USD',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $quote,
            ],
        ];
    }
}

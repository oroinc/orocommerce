<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;
use OroB2B\Bundle\SaleBundle\Validator\Constraints\ConfigurableQuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Validator\Constraints\ConfigurableQuoteProductOfferValidator;

class ConfigurableQuoteProductOfferTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var ConfigurableQuoteProductOffer
     */
    protected $constraint;

    /**
     * @var ConfigurableQuoteProductOfferValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->constraint = new ConfigurableQuoteProductOffer();
        $validatedBy = $this->constraint->validatedBy();
        $this->validator = new $validatedBy();
        $this->validator->initialize($this->context);
    }

    /**
     * @dataProvider validatorDataProvider
     * @param mixed $value
     * @param null|string $errorPath
     * @param null|string $errorMessage
     */
    public function testValidate($value, $errorMessage = null, $errorPath = null)
    {
        if ($errorMessage) {
            $this->assertViolationCall($errorMessage, $errorPath);
        } else {
            $this->context->expects($this->never())
                ->method($this->anything());
        }

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validatorDataProvider()
    {
        $offer = new QuoteProductOffer();
        $offer->setQuantity(10);

        $moreOffer = new QuoteProductOffer();
        $moreOffer->setAllowIncrements(true);
        $moreOffer->setQuantity(10);

        return [
            [
                null,
                'orob2b.sale.quoteproductoffer.configurable.offer.blank',
                QuoteProductToOrderType::FIELD_OFFER
            ],
            [
                [],
                'orob2b.sale.quoteproductoffer.configurable.offer.blank',
                QuoteProductToOrderType::FIELD_OFFER
            ],
            [
                [QuoteProductToOrderType::FIELD_QUANTITY => 10],
                'orob2b.sale.quoteproductoffer.configurable.offer.blank',
                QuoteProductToOrderType::FIELD_OFFER
            ],
            [
                [
                    QuoteProductToOrderType::FIELD_OFFER => new \stdClass(),
                    QuoteProductToOrderType::FIELD_QUANTITY => 10
                ],
                'orob2b.sale.quoteproductoffer.configurable.offer.blank',
                QuoteProductToOrderType::FIELD_OFFER
            ],
            [
                [
                    QuoteProductToOrderType::FIELD_OFFER => $offer,
                    QuoteProductToOrderType::FIELD_QUANTITY => $offer->getQuantity() - 5
                ],
                'orob2b.sale.quoteproductoffer.configurable.quantity.equal',
                QuoteProductToOrderType::FIELD_QUANTITY
            ],
            [
                [
                    QuoteProductToOrderType::FIELD_OFFER => $offer,
                    QuoteProductToOrderType::FIELD_QUANTITY => $offer->getQuantity() + 5
                ],
                'orob2b.sale.quoteproductoffer.configurable.quantity.equal',
                QuoteProductToOrderType::FIELD_QUANTITY
            ],
            [
                [
                    QuoteProductToOrderType::FIELD_OFFER => $moreOffer,
                    QuoteProductToOrderType::FIELD_QUANTITY => $moreOffer->getQuantity() - 5
                ],
                'orob2b.sale.quoteproductoffer.configurable.quantity.less',
                QuoteProductToOrderType::FIELD_QUANTITY
            ],
            [
                [
                    QuoteProductToOrderType::FIELD_OFFER => $moreOffer,
                    QuoteProductToOrderType::FIELD_QUANTITY => $moreOffer->getQuantity() + 5
                ],
                null,
                null
            ],
            [
                [
                    QuoteProductToOrderType::FIELD_OFFER => $moreOffer,
                    QuoteProductToOrderType::FIELD_QUANTITY => $moreOffer->getQuantity()
                ],
                null,
                null
            ],
            [
                [
                    QuoteProductToOrderType::FIELD_OFFER => $offer,
                    QuoteProductToOrderType::FIELD_QUANTITY => $offer->getQuantity()
                ],
                null,
                null
            ],
        ];
    }

    /**
     * @param string $message
     * @param string $path
     */
    protected function assertViolationCall($message, $path)
    {
        $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $violation->expects($this->any())
            ->method('atPath')
            ->with('[' . $path . ']')
            ->will($this->returnSelf());

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message)
            ->will($this->returnValue($violation));
    }
}

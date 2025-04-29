<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Validator\Constraints\QuoteProductRequestSource;
use Oro\Bundle\SaleBundle\Validator\Constraints\QuoteProductRequestSourceValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteProductRequestSourceValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): QuoteProductRequestSourceValidator
    {
        return new QuoteProductRequestSourceValidator();
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new QuoteProductRequest(), $this->createMock(Constraint::class));
    }

    public function testNullValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(null, new QuoteProductRequestSource());
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new QuoteProductRequestSource());
    }

    public function testNullRequestProductItem(): void
    {
        $quoteProductRequest = new QuoteProductRequest();

        $this->validator->validate($quoteProductRequest, new QuoteProductRequestSource());

        $this->assertNoViolation();
    }

    public function testNullRequestProduct(): void
    {
        $quoteProductRequest = new QuoteProductRequest();
        $quoteProductRequest->setRequestProductItem(new RequestProductItem());

        $this->validator->validate($quoteProductRequest, new QuoteProductRequestSource());

        $this->assertNoViolation();
    }

    public function testNullRequestProductRequest(): void
    {
        $quoteProductRequest = new QuoteProductRequest();
        $requestProductItem = new RequestProductItem();
        $quoteProductRequest->setRequestProductItem($requestProductItem);
        $requestProductItem->setRequestProduct(new RequestProduct());

        $this->validator->validate($quoteProductRequest, new QuoteProductRequestSource());

        $this->assertNoViolation();
    }

    public function testNoQuoteProduct(): void
    {
        $quoteProductRequest = new QuoteProductRequest();
        $requestProductItem = new RequestProductItem();
        $requestProduct = new RequestProduct();
        $quoteProductRequest->setRequestProductItem($requestProductItem);
        $requestProductItem->setRequestProduct($requestProduct);
        $requestProduct->setRequest(new Request());

        $constraint = new QuoteProductRequestSource();
        $this->validator->validate($quoteProductRequest, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.requestProductItem')
            ->assertRaised();
    }

    public function testNoQuote(): void
    {
        $quoteProductRequest = new QuoteProductRequest();
        $requestProductItem = new RequestProductItem();
        $requestProduct = new RequestProduct();
        $quoteProductRequest->setRequestProductItem($requestProductItem);
        $requestProductItem->setRequestProduct($requestProduct);
        $requestProduct->setRequest(new Request());

        $quoteProduct = new QuoteProduct();
        $quoteProductRequest->setQuoteProduct($quoteProduct);

        $constraint = new QuoteProductRequestSource();
        $this->validator->validate($quoteProductRequest, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.requestProductItem')
            ->assertRaised();
    }

    public function testNoQuoteRequest(): void
    {
        $quoteProductRequest = new QuoteProductRequest();
        $requestProductItem = new RequestProductItem();
        $requestProduct = new RequestProduct();
        $quoteProductRequest->setRequestProductItem($requestProductItem);
        $requestProductItem->setRequestProduct($requestProduct);
        $requestProduct->setRequest(new Request());

        $quoteProduct = new QuoteProduct();
        $quote = new Quote();
        $quoteProductRequest->setQuoteProduct($quoteProduct);
        $quoteProduct->setQuote($quote);

        $constraint = new QuoteProductRequestSource();
        $this->validator->validate($quoteProductRequest, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.requestProductItem')
            ->assertRaised();
    }

    public function testSomeSourceForQuoteProductRequestAndQuote(): void
    {
        $request = new Request();

        $quoteProductRequest = new QuoteProductRequest();
        $requestProductItem = new RequestProductItem();
        $requestProduct = new RequestProduct();
        $quoteProductRequest->setRequestProductItem($requestProductItem);
        $requestProductItem->setRequestProduct($requestProduct);
        $requestProduct->setRequest($request);

        $quoteProduct = new QuoteProduct();
        $quote = new Quote();
        $quoteProductRequest->setQuoteProduct($quoteProduct);
        $quoteProduct->setQuote($quote);
        $quote->setRequest($request);

        $this->validator->validate($quoteProductRequest, new QuoteProductRequestSource());

        $this->assertNoViolation();
    }

    public function testDifferentSourcesForQuoteProductRequestAndQuote(): void
    {
        $quoteProductRequest = new QuoteProductRequest();
        $requestProductItem = new RequestProductItem();
        $requestProduct = new RequestProduct();
        $quoteProductRequest->setRequestProductItem($requestProductItem);
        $requestProductItem->setRequestProduct($requestProduct);
        $requestProduct->setRequest(new Request());

        $quoteProduct = new QuoteProduct();
        $quote = new Quote();
        $quoteProductRequest->setQuoteProduct($quoteProduct);
        $quoteProduct->setQuote($quote);
        $quote->setRequest(new Request());

        $constraint = new QuoteProductRequestSource();
        $this->validator->validate($quoteProductRequest, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.requestProductItem')
            ->assertRaised();
    }
}

<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter;
use Oro\Bundle\SaleBundle\Validator\Constraints;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Form\FormInterface;

abstract class AbstractTest extends FormIntegrationTestCase
{
    protected const QP_TYPE1 = QuoteProduct::TYPE_REQUESTED;
    protected const QPO_PRICE_TYPE1 = QuoteProductOffer::PRICE_TYPE_UNIT;

    /** @var QuoteProductFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $quoteProductFormatter;

    /** @var QuoteProductOfferFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $quoteProductOfferFormatter;

    protected function configureQuoteProductOfferFormatter()
    {
        $this->quoteProductFormatter = $this->createMock(QuoteProductFormatter::class);
        $this->quoteProductFormatter->expects(self::any())
            ->method('formatTypeLabels')
            ->willReturnArgument(0);

        $this->quoteProductOfferFormatter = $this->createMock(QuoteProductOfferFormatter::class);
        $this->quoteProductOfferFormatter->expects(self::any())
            ->method('formatPriceTypeLabels')
            ->willReturnArgument(0);
    }

    abstract protected function createForm(mixed $data, array $options): FormInterface;

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        bool $isValid,
        array $submittedData,
        mixed $expectedData,
        mixed $defaultData = null,
        array $options = []
    ) {
        $form = $this->createForm($defaultData, $options);

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        self::assertEquals($isValid, $form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertEquals($expectedData, $form->getData());
    }

    abstract public function submitProvider(): array;

    #[\Override]
    protected function getValidators(): array
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects(self::any())
            ->method('round')
            ->willReturnCallback(function ($quantity) {
                return (float)$quantity;
            });

        return [
            'oro_sale.validator.quote_product' => new Constraints\QuoteProductValidator(),
            'doctrine.orm.validator.unique' => $this->createMock(UniqueEntityValidator::class),
            'oro_product_quantity_unit_precision' => new QuantityUnitPrecisionValidator($roundingService)
        ];
    }

    protected function getPriceType(): PriceType
    {
        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        return $priceType;
    }

    protected function getProductEntityType(): EntityTypeStub
    {
        return new EntityTypeStub([
            2 => $this->getProduct(2),
            3 => $this->getProduct(3),
        ]);
    }

    /**
     * @param string[] $codes
     *
     * @return ProductUnit[]
     */
    protected function getProductUnits(array $codes): array
    {
        $res = [];
        foreach ($codes as $code) {
            $res[] = (new ProductUnit())->setCode($code);
        }

        return $res;
    }

    /**
     * @return ProductUnitPrecision[]
     */
    protected function getProductUnitPrecisions(): array
    {
        return [
            (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('kg')),
            (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item')),
        ];
    }

    protected function getProductUnitSelectionType(): EntityTypeStub
    {
        return new ProductUnitSelectionTypeStub([
            'kg' => $this->getProductUnit('kg', 'code'),
            'item' => $this->getProductUnit('item', 'code'),
        ]);
    }

    /**
     * @template T
     * @psalm-param class-string<T> $className
     * @psalm-return T
     */
    protected function getEntity(string $className, int|string $id, string $primaryKey = 'id'): object
    {
        static $entities = [];
        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className();
            ReflectionUtil::setPropertyValue($entities[$className][$id], $primaryKey, $id);
        }

        return $entities[$className][$id];
    }

    protected function getUser(int $id): User
    {
        return $this->getEntity(User::class, $id);
    }

    protected function getProduct(int $id): Product
    {
        return $this->getEntity(Product::class, $id);
    }

    protected function getProductUnit(string $id, string $primaryKey): ProductUnit
    {
        return $this->getEntity(ProductUnit::class, $id, $primaryKey);
    }

    /**
     * @param int                   $productId
     * @param int|null $type
     * @param string|null $comment
     * @param string|null $commentCustomer
     * @param QuoteProductRequest[] $requests
     * @param QuoteProductOffer[]   $offers
     *
     * @return QuoteProduct
     */
    protected function getQuoteProduct(
        int $productId,
        int $type = null,
        string $comment = null,
        string $commentCustomer = null,
        array $requests = [],
        array $offers = []
    ): QuoteProduct {
        $product = $this->getProduct($productId);

        foreach ($this->getProductUnitPrecisions() as $precision) {
            $product->addUnitPrecision($precision);
        }

        $quoteProduct = new QuoteProduct();
        $quoteProduct
            ->setQuote($this->getEntity(Quote::class, $productId))
            ->setProduct($product)
            ->setType($type)
            ->setComment($comment)
            ->setCommentCustomer($commentCustomer);

        foreach ($requests as $request) {
            $quoteProduct->addQuoteProductRequest($request);
        }

        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        return $quoteProduct;
    }

    protected function getQuoteProductOffer(
        int $productId = null,
        float $quantity = null,
        string $unitCode = null,
        int $priceType = null,
        Price $price = null
    ): QuoteProductOffer {
        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setQuoteProduct($this->getQuoteProduct($productId));

        if (null !== $quantity) {
            $quoteProductOffer->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $quoteProductOffer->setProductUnit($this->getProductUnit($unitCode, 'code'));
        }

        $quoteProductOffer->setPriceType($priceType);

        if (null !== $price) {
            $quoteProductOffer->setPrice($price);
        }

        return $quoteProductOffer;
    }

    protected function getQuoteProductRequest(
        int $productId = null,
        float $quantity = null,
        string $unitCode = null,
        Price $price = null
    ): QuoteProductRequest {
        $quoteProductRequest = new QuoteProductRequest();
        $quoteProductRequest->setQuoteProduct($this->getQuoteProduct($productId));

        if (null !== $quantity) {
            $quoteProductRequest->setQuantity($quantity);
        }

        if (null !== $unitCode) {
            $quoteProductRequest->setProductUnit($this->getProductUnit($unitCode, 'code'));
        }

        if (null !== $price) {
            $quoteProductRequest->setPrice($price);
        }

        return $quoteProductRequest;
    }
}

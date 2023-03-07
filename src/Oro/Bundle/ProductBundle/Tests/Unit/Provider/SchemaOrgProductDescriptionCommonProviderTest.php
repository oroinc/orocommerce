<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityExtendBundle\EntityExtend\PropertyAccessorWithDotArraySyntax;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Provider\SchemaOrgProductDescriptionCommonProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class SchemaOrgProductDescriptionCommonProviderTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD = 'descriptions';
    private const PRODUCT_FULL_DESCRIPTION_WITH_TAGS = '<p>test_full_description</p>';
    private const PRODUCT_FULL_DESCRIPTION_WITHOUT_TAGS = 'test_full_description';

    private SchemaOrgProductDescriptionCommonProvider $productDescriptionProvider;

    protected function setUp(): void
    {
        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $propertyAccessor = $this->createMock(PropertyAccessorWithDotArraySyntax::class);
        $localizationHelper = $this->createMock(LocalizationHelper::class);

        $propertyAccessor
            ->expects(self::any())
            ->method('getValue')
            ->with($this->getProduct(), self::FIELD)
            ->willReturn(new ArrayCollection([$this->getProductDescription()]));

        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->with(new ArrayCollection([$this->getProductDescription()]), new LocalizationStub(1))
            ->willReturn($this->getProductDescription());

        $htmlTagHelper
            ->expects(self::any())
            ->method('stripTags')
            ->with($this->getProductDescription())
            ->willReturn(self::PRODUCT_FULL_DESCRIPTION_WITHOUT_TAGS);

        $this->productDescriptionProvider = new SchemaOrgProductDescriptionCommonProvider(
            $propertyAccessor,
            $htmlTagHelper,
            $localizationHelper,
            self::FIELD
        );
    }

    public function testGetDescription(): void
    {
        self::assertEquals(
            self::PRODUCT_FULL_DESCRIPTION_WITHOUT_TAGS,
            $this->productDescriptionProvider->getDescription(
                $this->getProduct(),
                new LocalizationStub(1)
            )
        );
    }

    private function getProduct(): Product
    {
        $product = new ProductStub();
        $description = $this->getProductDescription();
        $product->addDescription($description);

        return $product;
    }

    private function getProductDescription(): ProductDescription
    {
        $description = new ProductDescription();
        $description->setLocalization(new LocalizationStub(1));
        $description->setWysiwyg(self::PRODUCT_FULL_DESCRIPTION_WITH_TAGS);

        return $description;
    }
}

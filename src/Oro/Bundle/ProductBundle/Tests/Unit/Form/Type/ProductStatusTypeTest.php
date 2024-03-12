<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductStatusType;
use Oro\Bundle\ProductBundle\Provider\ProductStatusProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductStatusTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductStatusProvider */
    private $productStatusProvider;

    /** @var ProductStatusType */
    private $productStatusType;

    protected function setUp(): void
    {
        $this->productStatusProvider = $this->createMock(ProductStatusProvider::class);

        $this->productStatusProvider->expects(self::any())
            ->method('getAvailableProductStatuses')
            ->willReturn([
                'Disabled' => Product::STATUS_DISABLED,
                'Enabled' => Product::STATUS_ENABLED,
            ]);

        $this->productStatusType = new ProductStatusType($this->productStatusProvider);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->productStatusType], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->productStatusType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create(ProductStatusType::class);

        $this->assertEquals(
            [new ChoiceView(Product::STATUS_DISABLED, Product::STATUS_DISABLED, 'Disabled')],
            $form->createView()->vars['preferred_choices']
        );

        $this->assertEquals(
            [1 => new ChoiceView(Product::STATUS_ENABLED, Product::STATUS_ENABLED, 'Enabled')],
            $form->createView()->vars['choices']
        );

        $this->assertFalse(
            $form->getConfig()->getOptions()['duplicate_preferred_choices']
        );

        $this->assertEquals(
            [Product::STATUS_DISABLED],
            $form->getConfig()->getOptions()['preferred_choices']
        );
    }
}

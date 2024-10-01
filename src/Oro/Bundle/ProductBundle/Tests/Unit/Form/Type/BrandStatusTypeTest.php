<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Form\Type\BrandStatusType;
use Oro\Bundle\ProductBundle\Provider\BrandStatusProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class BrandStatusTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|BrandStatusProvider */
    private $brandStatusProvider;

    /** @var BrandStatusType */
    private $brandStatusType;

    #[\Override]
    protected function setUp(): void
    {
        $this->brandStatusProvider = $this->createMock(BrandStatusProvider::class);

        $this->brandStatusProvider->expects(self::any())
            ->method('getAvailableBrandStatuses')
            ->willReturn([
                'Disabled' => Brand::STATUS_DISABLED,
                'Enabled' => Brand::STATUS_ENABLED,
            ]);

        $this->brandStatusType = new BrandStatusType($this->brandStatusProvider);
        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->brandStatusType], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->brandStatusType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create(BrandStatusType::class);

        $this->assertEquals(
            [1 => new ChoiceView(Brand::STATUS_ENABLED, Brand::STATUS_ENABLED, 'Enabled')],
            $form->createView()->vars['choices']
        );

        $this->assertEquals(
            [new ChoiceView(Brand::STATUS_DISABLED, Brand::STATUS_DISABLED, 'Disabled')],
            $form->createView()->vars['preferred_choices']
        );

        $this->assertFalse(
            $form->getConfig()->getOptions()['duplicate_preferred_choices']
        );

        $this->assertEquals(
            [Brand::STATUS_DISABLED],
            $form->getConfig()->getOptions()['preferred_choices']
        );
    }
}

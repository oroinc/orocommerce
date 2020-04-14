<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Form\Type\BrandStatusType;
use Oro\Bundle\ProductBundle\Provider\BrandStatusProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class BrandStatusTypeTest extends FormIntegrationTestCase
{
    /** @var  BrandStatusType $brandStatusType */
    protected $brandStatusType;

    /** @var \PHPUnit\Framework\MockObject\MockObject|BrandStatusProvider $brandStatusProvider */
    protected $brandStatusProvider;

    protected function setUp(): void
    {
        $this->brandStatusProvider =
            $this->getMockBuilder('Oro\Bundle\ProductBundle\Provider\BrandStatusProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->brandStatusProvider
            ->method('getAvailableBrandStatuses')
            ->willReturn([
                'Disabled' => Brand::STATUS_DISABLED,
                'Enabled' => Brand::STATUS_ENABLED,
            ]);

        $this->brandStatusType = new BrandStatusType($this->brandStatusProvider);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([$this->brandStatusType], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(
            \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
            $this->brandStatusType->getParent()
        );
    }

    public function testChoices()
    {
        $form = $this->factory->create(BrandStatusType::class);
        $availableBrandStatuses = $this->brandStatusProvider->getAvailableBrandStatuses();
        $choices = [];

        foreach ($availableBrandStatuses as $label => $value) {
            $choices[] = new ChoiceView($value, $value, $label);
        }

        $this->assertEquals(
            $choices,
            $form->createView()->vars['choices']
        );

        $this->assertEquals(
            Brand::STATUS_DISABLED,
            $form->getConfig()->getOptions()['preferred_choices']
        );
    }
}

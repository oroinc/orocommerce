<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SEOBundle\Form\Type\SitemapPriorityType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class SitemapPriorityTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SitemapPriorityType
     */
    private $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->type = new SitemapPriorityType();

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SitemapPriorityType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(NumberType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'constraints' => [
                        new Range(['min' => 0, 'max' => 1]),
                        new Decimal(),
                    ],
                ]
            );

        $this->type->configureOptions($resolver);
    }
}

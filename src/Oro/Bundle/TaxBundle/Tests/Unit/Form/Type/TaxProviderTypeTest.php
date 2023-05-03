<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Form\Type\TaxProviderType;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxProviderTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var TaxProviderType */
    private $formType;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(TaxProviderRegistry::class);

        $this->formType = new TaxProviderType($this->registry);
    }

    private function getProvider(string $label): TaxProviderInterface
    {
        $taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProvider->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        return $taxProvider;
    }

    public function testConfigureOptions()
    {
        $this->registry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                'name1' => $this->getProvider('label1'),
                'name2' => $this->getProvider('label2')
            ]);

        $resolver = new OptionsResolver();

        $this->formType->configureOptions($resolver);

        $options = $resolver->resolve([]);
        $this->assertArrayHasKey('choices', $options);
        $this->assertEquals(['label1' => 'name1', 'label2' => 'name2'], $options['choices']);
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}

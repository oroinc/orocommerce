<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\PaymentBundle\Provider\PaymentMethodObjectVariablesProvider;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;

class PaymentMethodObjectVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentMethodObjectVariablesProvider */
    private $provider;

    protected function setUp()
    {
        $this->provider = new PaymentMethodObjectVariablesProvider();
    }

    public function testGetVariableDefinitions()
    {
        self::assertSame([], $this->provider->getVariableDefinitions());
        self::assertSame([], $this->provider->getVariableDefinitions(PaymentMethodObject::class));
    }

    public function testGetVariableProcessors()
    {
        self::assertSame([], $this->provider->getVariableProcessors(PaymentMethodObject::class));
    }

    public function testGetVariableGetters()
    {
        self::assertEquals(
            [
                PaymentMethodObject::class => [
                    'label'   => 'getLabel',
                    'options' => 'getOptions'
                ]
            ],
            $this->provider->getVariableGetters()
        );
    }
}

<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\PaymentBundle\Provider\PaymentMethodObjectVariablesProvider;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;

class PaymentMethodObjectVariablesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodObjectVariablesProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->provider = new PaymentMethodObjectVariablesProvider();
    }

    public function testGetVariableDefinitions()
    {
        self::assertCount(0, $this->provider->getVariableDefinitions());
    }

    public function testGetVariableGetters()
    {
        self::assertCount(1, $this->provider->getVariableGetters());
        self::assertEquals(
            [PaymentMethodObject::class => ['getLabel', 'getOptions']],
            $this->provider->getVariableGetters()
        );
    }
}

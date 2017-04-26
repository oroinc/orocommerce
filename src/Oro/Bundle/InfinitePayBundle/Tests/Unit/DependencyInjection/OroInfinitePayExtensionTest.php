<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\DependencyInjection;

use Oro\Bundle\InfinitePayBundle\DependencyInjection\OroInfinitePayExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

/**
 * {@inheritdoc}
 */
class OroInfinitePayExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroInfinitePayExtension());

        $expectedParameters = [
            'infitepay.wsdl.url',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_infinite_pay.client.factory',
            'oro_infinite_pay.soap.gateway',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}

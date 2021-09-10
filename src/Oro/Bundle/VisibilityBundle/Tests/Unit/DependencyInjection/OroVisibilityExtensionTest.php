<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\AbstractPrependExtensionTest;
use Oro\Bundle\VisibilityBundle\DependencyInjection\OroVisibilityExtension;

class OroVisibilityExtensionTest extends AbstractPrependExtensionTest
{
    public function testExtension()
    {
        $extension = new OroVisibilityExtension();

        $this->loadExtension($extension);

        $this->assertEquals('oro_visibility', $extension->getAlias());
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroVisibilityExtension::ALIAS, $this->getExtension()->getAlias());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtension()
    {
        return new OroVisibilityExtension();
    }
}

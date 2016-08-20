<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\DuplicatorFilterPass;

class DuplicatorFilterPassTest extends AbstractDuplicatorPassTest
{
    public function setUp()
    {
        $this->compilerPass = new DuplicatorFilterPass();
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return DuplicatorFilterPass::FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return DuplicatorFilterPass::TAG_NAME;
    }
}

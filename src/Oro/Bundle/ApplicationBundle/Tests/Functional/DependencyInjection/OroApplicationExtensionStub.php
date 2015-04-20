<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Functional\DependencyInjection;

use Oro\Bundle\ApplicationBundle\DependencyInjection\OroApplicationExtension;

class OroApplicationExtensionStub extends OroApplicationExtension
{
    /**
     * @return string
     */
    protected function getRootDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritDoc}
     */
    protected function findConfigurations($targetFile, $roots = [])
    {
        return [
            $this->getRootDir() . 'roles.yml',
        ];
    }
}

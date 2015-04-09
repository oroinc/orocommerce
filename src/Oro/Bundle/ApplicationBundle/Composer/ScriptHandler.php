<?php

namespace Oro\Bundle\ApplicationBundle\Composer;

use Composer\Script\CommandEvent;

use Oro\Bundle\InstallerBundle\Composer\PermissionsHandler;
use Oro\Bundle\InstallerBundle\Composer\ScriptHandler as BasicScriptHandler;

class ScriptHandler extends BasicScriptHandler
{
    /**
     * {@inheritdoc}
     */
    public static function setPermissions(CommandEvent $event)
    {
        $options = self::getOptions($event);

        $webDir = isset($options['symfony-web-dir']) ?
            $options['symfony-web-dir'] : 'web';

        $parametersFile = isset($options['incenteev-parameters']['file']) ?
            $options['incenteev-parameters']['file'] : 'app/common/parameters.yml';

        $directories = [
            'var/cache',
            'var/logs',
            'var/attachment',
            $webDir,
            $parametersFile
        ];

        $permissionHandler = new PermissionsHandler();
        foreach ($directories as $directory) {
            $permissionHandler->setPermissions($directory);
        }
    }
}

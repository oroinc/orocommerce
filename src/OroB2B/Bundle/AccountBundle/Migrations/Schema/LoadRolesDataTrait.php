<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema;

use OroB2B\Bundle\AccountBundle\Migrations\Data\ORM\LoadAccountUserRoles;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

/**
 * This trait helps to avoid writing boilerplate and duplicated code
 * when adding new migration related to Account User Roles.
 *
 * Developer can use loadRolesData method to get all roles declared in yaml
 * files, that may require updates after changes in the schema.
 */
trait LoadRolesDataTrait
{
    /**
     * @param $bundle
     * @param $fileName
     * @return string
     */
    protected function getFullFileName($bundle, $fileName)
    {
        return sprintf(
            '@%s%s%s',
            $bundle,
            '/Migrations/Data/ORM/data/',
            $fileName
        );
    }

    /**
     * @return array
     */
    protected function loadRolesData()
    {
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $bundles = array_keys($this->container->getParameter('kernel.bundles'));

        $rolesData = [];
        foreach ($bundles as $bundle) {
            $fileName = $this->getFullFileName(
                $bundle,
                LoadAccountUserRoles::ROLES_FILE_NAME
            );

            try {
                $file = $kernel->locateResource($fileName);
                $rolesData = array_merge_recursive($rolesData, Yaml::parse($file));
            } catch (\InvalidArgumentException $e) {
            }
        }

        $file = $kernel->locateResource(
            $this->getFullFileName('OroB2BAccountBundle', 'anonymous_role.yml')
        );
        $rolesData = array_merge_recursive($rolesData, Yaml::parse($file));

        return $rolesData;
    }
}

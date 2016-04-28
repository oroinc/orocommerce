<?php

namespace OroB2B\Bundle\FrontendBundle\DependencyInjection\Loader;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PrivateYamlFileLoader extends YamlFileLoader
{
    /**
     * {@inheritdoc}
     */
    protected function loadFile($file)
    {
        $fileData = parent::loadFile($file);

        if (!empty($fileData['services'])) {
            foreach ($fileData['services'] as $id => $definition) {
                if (!array_key_exists('public', $definition)) {
                    $fileData['services'][$id]['public'] = false;
                }
            }
        }

        return $fileData;
    }
}

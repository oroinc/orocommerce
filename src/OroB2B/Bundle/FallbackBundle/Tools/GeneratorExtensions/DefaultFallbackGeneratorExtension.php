<?php

namespace OroB2B\Bundle\FallbackBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;

class DefaultFallbackGeneratorExtension extends AbstractEntityGeneratorExtension
{
    const DEFAULT_GETTER_PREFIX = 'getDefault';

    protected $methodExtensions = [];

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        if (!isset($schema['class'])) {
            return false;
        }

        return isset($this->methodExtensions[$schema['class']]);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        $fields = $this->methodExtensions[$schema['class']];

        if (empty($fields)) {
            return;
        }

        foreach ($fields as $field) {
            $this->generateDefaultGetter($field, $class);
        }
    }

    public function addMethodExtension($className, $fields)
    {
        $this->methodExtensions[$className] = $fields;
    }

    protected function generateDefaultGetter($fieldName, PhpClass $class)
    {
        $methodBody = [
            '$values = $this->'. $fieldName . '->filter(function (\OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue $value) {',
            '   return null === $value->getLocale();',
            '});',
            'if ($values->count() > 1) {',
            '   throw new \LogicException(\'There must be only one default short description\');',
            '}',
            'return $values->first();'
        ];

        $method = $this->generateClassMethod($this->getDefaultGetterMethodName($fieldName), implode("\n", $methodBody));
        $class->setMethod($method);
    }

    protected function getDefaultGetterMethodName($fieldName)
    {
        return self::DEFAULT_GETTER_PREFIX . ucfirst(Inflector::camelize($fieldName));
    }
}

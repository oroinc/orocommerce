<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase as BaseTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator;

class FormIntegrationTestCase extends BaseTestCase
{
    /**
     * @param bool $loadMetadata
     * @return ValidatorExtension
     */
    protected function getValidatorExtension($loadMetadata = false)
    {
        return new ValidatorExtension($loadMetadata ? $this->getValidator() : Validation::createValidator());
    }

    /**
     * @return Validator
     */
    protected function getValidator() {
        /* @var $loader \PHPUnit_Framework_MockObject_MockObject|LoaderInterface */
        $loader = $this->getMock('Symfony\Component\Validator\Mapping\Loader\LoaderInterface');
        $loader
            ->expects($this->any())
            ->method('loadClassMetadata')
            ->will($this->returnCallback(function (ClassMetadata $meta) {
                $this->loadMetadata($meta);
            }));

        $validator = new Validator(
            new ClassMetadataFactory($loader),
            new ConstraintValidatorFactory(),
            $this->getTranslator()
        );

        return $validator;
    }

    /**
     * @param ClassMetadata $meta
     */
    protected function loadMetadata(ClassMetadata $meta)
    {
        if (FALSE !== ($configFile = $this->getConfigPath($meta->name))) {
            $loader = new YamlFileLoader($configFile);
            $loader->loadClassMetadata($meta);
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected function getTranslator()
    {
        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }))
        ;
        $translator->expects($this->any())
            ->method('transChoice')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }))
        ;

        return $translator;
    }

    /**
     * @param string $class
     * @return string
     */
    function getBundleRootPath($class)
    {
        $rclass = new \ReflectionClass($class);

        $path = false;

        if (FALSE !== $pos = strrpos($rclass->getFileName(), 'Bundle')) {
            $path = substr($rclass->getFileName(), 0, $pos) . 'Bundle';
        }

        return $path;
    }

    /**
     * @param string $class
     * @return string
     */
    public function getConfigPath($class)
    {
        $path = $this->getBundleRootPath($class);

        if ($path) {
            $path .= '/Resources/config/validation.yml';
        }

        return $path;
    }
}

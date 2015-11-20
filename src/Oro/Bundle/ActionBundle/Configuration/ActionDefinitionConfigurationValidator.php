<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use AppKernel;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionDefinitionConfigurationValidator
{
    /**
     * @var AppKernel
     */
    protected $kernel;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param RouterInterface $router
     * @param EngineInterface $templating
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        AppKernel $kernel,
        RouterInterface $router,
        EngineInterface $templating,
        DoctrineHelper $doctrineHelper
    ) {
        $this->kernel = $kernel;
        $this->router = $router;
        $this->templating = $templating;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $configuration
     */
    public function validate(array $configuration)
    {
        foreach ($configuration as $name => $action) {
            $this->validateAction($name, $action);
        }
    }

    /**
     * @param sting $path
     * @param array $action
     */
    protected function validateAction($path, array $action)
    {
        $this->validateFrontendOptions($this->getPath($path, 'frontend_options'), $action['frontend_options']);
        $this->validateRoutes($this->getPath($path, 'routes'), $action['routes']);
        $this->validateEntities($this->getPath($path, 'entities'), $action['entities']);
    }

    /**
     * @param string $path
     * @param array $options
     */
    protected function validateFrontendOptions($path, array $options)
    {
        if (isset($options['template']) && !$this->validateTemplate($options['template'])) {
            $this->showException(
                $this->getPath($path, 'template'),
                'Unable to find template "%s"',
                $options['template'],
                false
            );
        }

    }

    /**
     * @param string $path
     * @param array $items
     */
    protected function validateRoutes($path, array $items)
    {
        foreach ($items as $key => $item) {
            if (!$this->validateRoute($item)) {
                $this->showException($this->getPath($path, $key), 'Route "%s" not found.', $item);
            }
        }
    }

    /**
     * @param string $path
     * @param array $items
     */
    protected function validateEntities($path, array $items)
    {
        foreach ($items as $key => $item) {
            if (!$this->validateEntity($item)) {
                $this->showException($this->getPath($path, $key), 'Entity "%s" not found.', $item);
            }
        }
    }

    /**
     * @param string $routeName
     * @return boolean
     */
    public function validateRoute($routeName)
    {
        return null !== $this->router->getRouteCollection()->get($routeName);
    }

    /**
     * @param string $templateName
     * @return boolean
     */
    public function validateTemplate($templateName)
    {
        return $this->templating->exists($templateName);
    }

    /**
     * @param string $entityName
     * @return boolean
     */
    public function validateEntity($entityName)
    {
        try {
            $entityClass = $this->doctrineHelper->getEntityClass($entityName);

            if (!class_exists($entityClass, true)) {
                return false;
            }

            $reflection = new \ReflectionClass($entityClass);

            return $this->doctrineHelper->isManageableEntity($reflection->getName());
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @param string $path
     * @param string $subpath
     * @return string
     */
    protected function getPath($path, $subpath)
    {
        return $path . '.' . $subpath;
    }

    /**
     * @param string $path
     * @param string $message
     * @param mixed $value
     * @param bool $silent
     */
    protected function showException($path, $message, $value, $silent = true)
    {
        $errorMessage = sprintf($message, $value);
        if (!$silent) {
            $exception = new InvalidConfigurationException($errorMessage);
            $exception->setPath($path);

            throw $exception;
        } elseif ($this->kernel->isDebug()) {
            print('InvalidConfiguration: ' . $path . ': '. $errorMessage . "\n");
        }
    }
}

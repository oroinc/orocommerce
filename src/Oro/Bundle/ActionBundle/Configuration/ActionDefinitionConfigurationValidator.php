<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionDefinitionConfigurationValidator
{
    /**
     * @var bool
     */
    protected $debug;

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
     * @param bool $debug
     * @param RouterInterface $router
     * @param EngineInterface $templating
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        $debug,
        RouterInterface $router,
        EngineInterface $templating,
        DoctrineHelper $doctrineHelper
    ) {
        $this->debug = $debug;
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
            $this->validateAction($action, $name);
        }
    }

    /**
     * @param array $action
     * @param string $path
     */
    protected function validateAction(array $action, $path)
    {
        $this->validateFrontendOptions($action['frontend_options'], $this->getPath($path, 'frontend_options'));
        $this->validateRoutes($action['routes'], $this->getPath($path, 'routes'));
        $this->validateEntities($action['entities'], $this->getPath($path, 'entities'));
    }

    /**
     * @param array $options
     * @param string $path
     */
    protected function validateFrontendOptions(array $options, $path)
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
     * @param array $items
     * @param string $path
     */
    protected function validateRoutes(array $items, $path)
    {
        foreach ($items as $key => $item) {
            if (!$this->validateRoute($item)) {
                $this->showException($this->getPath($path, $key), 'Route "%s" not found.', $item);
            }
        }
    }

    /**
     * @param array $items
     * @param string $path
     */
    protected function validateEntities(array $items, $path)
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
        } elseif ($this->debug) {
            print('InvalidConfiguration: ' . $path . ': '. $errorMessage . "\n");
        }
    }
}

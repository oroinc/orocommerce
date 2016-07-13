<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;

abstract class AbstractWarehouseInventoryLevelStrategyHelper implements WarehouseInventoryLevelStrategyHelperInterface
{
    /** @var  DatabaseHelper $databaseHelper */
    protected $databaseHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var  WarehouseInventoryLevelStrategyHelperInterface $successor */
    protected $successor;

    /** @var array $errors */
    protected $errors = [];

    /**
     * @param DatabaseHelper $databaseHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(DatabaseHelper $databaseHelper, TranslatorInterface $translator)
    {
        $this->databaseHelper = $databaseHelper;
        $this->translator = $translator;
    }

    /**
     * Using DatabaseHelper we search for an entity using its class name and
     * a criteria composed of a field from this entity and its value.
     * If entity is not found then add a validation error on the context.
     *
     * @param string $class
     * @param array $criteria
     * @param null|string $alternaiveClassName
     * @return null|object
     */
    protected function checkAndRetrieveEntity($class, array $criteria = [], $alternaiveClassName = null)
    {
        $existingEntity = $this->databaseHelper->findOneBy($class, $criteria);
        if (!$existingEntity) {
            $classNamespace = explode('\\', $class);
            $shortClassName = end($classNamespace);
            $this->addError(
                'orob2b.warehouse.import.error.not_found_entity',
                ['%entity%' => $alternaiveClassName ?: $shortClassName]
            );
        }

        return $existingEntity;
    }

    /**
     * Translates the received error and adds it to the list of errors
     *
     * @param string $error
     * @param array $translationParams
     * @param null|string $prefix
     */
    protected function addError($error, array $translationParams = [], $prefix = null)
    {
        $errorMessage = $this->translator->trans($error, $translationParams);

        if ($prefix) {
            $prefix = $this->translator->trans($prefix);
        }

        $this->errors[$errorMessage] = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors($deep = false)
    {
        $successorErrors = $this->successor ? $this->successor->getErrors(true) : [];

        return array_merge($this->errors, $successorErrors);
    }

    /**
     * {@inheritdoc}
     */
    public function setSuccessor(WarehouseInventoryLevelStrategyHelperInterface $successor)
    {
        $this->successor = $successor;
    }

    /**
     * Helper function which extracts an entity from an array based on a key.
     * @param array $entities
     * @param string $name
     * @return null
     */
    protected function getProcessedEntity($entities, $name)
    {
        return isset($entities[$name]) ? $entities[$name] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache($deep = false)
    {
        $this->errors = [];

        if ($deep && $this->successor) {
            $this->successor->clearCache($deep);
        }
    }
}

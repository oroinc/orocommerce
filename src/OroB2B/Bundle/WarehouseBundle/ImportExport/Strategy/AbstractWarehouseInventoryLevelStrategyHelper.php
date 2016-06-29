<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;

use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractWarehouseInventoryLevelStrategyHelper implements WarehouseInventoryLevelStrategyHelperInterface
{
    /** @var  DatabaseHelper $databaseHelper */
    protected $databaseHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var  WarehouseInventoryLevelStrategyHelperInterface $successor */
    protected $successor;

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
     * @return null|object
     */
    protected function checkAndRetrieveEntity($class, array $criteria = [])
    {
        $existingEntity = $this->databaseHelper->findOneBy($class, $criteria);
        if (!$existingEntity) {
            $this->addError(
                'orob2b.warehouse.import.error.not_found_entity',
                ['%entity%' => end(explode('\\', $class))]
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
     * @inheritdoc
     */
    public function getErrors($deep = false)
    {
        $successorErrors = $this->successor ? $this->successor->getErrors(true) : [];

        return array_merge($this->errors, $successorErrors);
    }

    /**
     * @inheritdoc
     */
    public function setSuccessor(WarehouseInventoryLevelStrategyHelperInterface $successor)
    {
        $this->successor = $successor;
    }
}

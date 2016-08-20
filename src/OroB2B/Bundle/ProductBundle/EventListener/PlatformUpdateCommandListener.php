<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleEvent;

use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;

/**
 * This listener should be executed only once during update from alpha1 to later version
 * TODO: remove this listener after stable release
 */
class PlatformUpdateCommandListener
{
    /**
     * @var ConfigModelManager
     */
    protected $configModelManager;

    /**
     * @param ConfigModelManager $configModelManager
     */
    public function __construct(ConfigModelManager $configModelManager)
    {
        $this->configModelManager = $configModelManager;
    }

    /**
     * Remove status enum metadata from DB
     *
     * @param ConsoleEvent $event
     */
    public function onConsoleCommand(ConsoleEvent $event)
    {
        if (!$event->getCommand() instanceof PlatformUpdateCommand) {
            return;
        }

        $productClass = 'Oro\Bundle\ProductBundle\Entity\Product';
        $statusField = 'status';
        $statusClass = 'Extend\Entity\EV_Prod_Status';

        $entityManager = $this->configModelManager->getEntityManager();

        $statusEntityModel = $this->configModelManager->findEntityModel($statusClass);
        if ($statusEntityModel) {
            foreach ($statusEntityModel->getFields() as $field) {
                $entityManager->remove($field);
            }
            $entityManager->remove($statusEntityModel);

            $statusFieldModel = $this->configModelManager->findFieldModel($productClass, $statusField);
            if ($statusFieldModel && $statusFieldModel->getType() === 'enum') {
                $entityManager->remove($statusFieldModel);
            }

            $productEntityModel = $this->configModelManager->findEntityModel($productClass);
            $extendData = $productEntityModel->toArray('extend');

            $extendKey = sprintf('manyToOne|%s|%s|%s', $productClass, $statusClass, $statusField);
            if (isset($extendData['relation'][$extendKey])) {
                unset($extendData['relation'][$extendKey]);
            }

            if (isset($extendData['schema']['relation'][$statusField])) {
                unset($extendData['schema']['relation'][$statusField]);
            }

            $productEntityModel->fromArray('extend', $extendData, []);

            $entityManager->flush();
        }
    }
}

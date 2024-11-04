<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\EventArgs;
use Gedmo\Tree\TreeListener as GedmoTreeListener;

/**
 * Adds ability to disable Gedmo TreeListener.
 * Needed to optimize performance during Category import.
 */
class TreeListener extends GedmoTreeListener
{
    /** @var bool */
    private $enabled = true;

    /**
     * Sets if current listener is enabled
     *
     * @param bool $enabled
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    #[\Override]
    public function onFlush(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::onFlush($args);
    }

    #[\Override]
    public function preRemove(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::preRemove($args);
    }

    #[\Override]
    public function prePersist(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::prePersist($args);
    }

    #[\Override]
    public function preUpdate(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::preUpdate($args);
    }

    #[\Override]
    public function postPersist(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::postPersist($args);
    }

    #[\Override]
    public function postUpdate(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::postUpdate($args);
    }

    #[\Override]
    public function postRemove(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::postRemove($args);
    }
}

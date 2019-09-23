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

    /**
     * {@inheritdoc}
     */
    public function onFlush(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::onFlush($args);
    }

    /**
     * {@inheritdoc}
     */
    public function preRemove(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::preRemove($args);
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::prePersist($args);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::preUpdate($args);
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::postPersist($args);
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdate(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::postUpdate($args);
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(EventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::postRemove($args);
    }
}

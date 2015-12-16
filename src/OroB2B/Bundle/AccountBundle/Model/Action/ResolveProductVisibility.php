<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class ResolveProductVisibility extends AbstractVisibilityRegistryAwareAction
{
    /**
     * @var bool
     */
    protected $resetVisibility = false;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $visibilityEntity = $this->getEntity($context);
        if ($this->resetVisibility) {
            $visibilityEntity->setVisibility($visibilityEntity::getDefault($visibilityEntity));
        }

        $this->getEntityManager()->transactional(
            function () use ($visibilityEntity) {
                $this->cacheBuilder->resolveVisibilitySettings($visibilityEntity);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity($context)
    {
        $entity = parent::getEntity($context);

        if (!$entity instanceof VisibilityInterface) {
            throw new \LogicException('Resolvable entity must implement VisibilityInterface');
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->resetVisibility = array_key_exists('reset_visibility', $options) && $options['reset_visibility'];

        return parent::initialize($options);
    }

    /**
     * All resolved product visibility entities should be stored together, so entity manager should be the same too
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }
}

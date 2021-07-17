<?php

namespace Oro\Bundle\RedirectBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Helper\SlugifyEntityHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets slug to entity if source exists and slug empty.
 */
class SetSlugs implements ProcessorInterface
{
    /**
     * @var SlugifyEntityHelper
     */
    private $slugifyEntityHelper;

    public function __construct(SlugifyEntityHelper $slugifyEntityHelper)
    {
        $this->slugifyEntityHelper = $slugifyEntityHelper;
    }

    /**
     * @param ContextInterface|CustomizeFormDataContext $context
     */
    public function process(ContextInterface $context): void
    {
        $entity = $context->getData();
        if ($entity instanceof SluggableInterface) {
            $this->slugifyEntityHelper->fill($entity);
        }
    }
}

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
    private SlugifyEntityHelper $slugifyEntityHelper;

    public function __construct(SlugifyEntityHelper $slugifyEntityHelper)
    {
        $this->slugifyEntityHelper = $slugifyEntityHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $entity = $context->getData();
        if ($entity instanceof SluggableInterface) {
            $this->slugifyEntityHelper->fill($entity);
        }
    }
}

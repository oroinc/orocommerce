<?php

namespace OroB2B\Bundle\OrderBundle\Twig;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;

use OroB2B\Bundle\OrderBundle\Provider\IdentifierAwareInterface;

class OrderExtension extends \Twig_Extension
{
    const NAME = 'orob2b_order_order';

    /**
     * @var ChainEntityClassNameProvider
     */
    protected $chainEntityClassNameProvider;

    /**
     * @param ChainEntityClassNameProvider $chainEntityClassNameProvider
     */
    public function __construct(ChainEntityClassNameProvider $chainEntityClassNameProvider)
    {
        $this->chainEntityClassNameProvider = $chainEntityClassNameProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_order_get_title_source_document',
                [$this, 'getTitleSourceDocument'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * @param $entity
     *
     * @return string
     */
    public function getTitleSourceDocument($entity)
    {
        $identifier = '';
        if ($entity instanceof IdentifierAwareInterface) {
            $identifier = $entity->getIdentifier();
        }

        $class = $this->chainEntityClassNameProvider->getEntityClassName(ClassUtils::getClass($entity));

        return sprintf('%s %s', $class, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}

<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;

class ActionLineButtonsType extends AbstractType
{
    const NAME = 'action_line_buttons';

    /**
     * @var  ApplicationsHelper
     */
    protected $applicationsHelper;

    /**
     * @param ApplicationsHelper $applicationsHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ApplicationsHelper $applicationsHelper, DoctrineHelper $doctrineHelper)
    {
        $this->applicationsHelper = $applicationsHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'dialogRoute' => $this->applicationsHelper->getDialogRoute(),
                'executionRoute' => $this->applicationsHelper->getExecutionRoute()
            ]
        );
        $resolver->setOptional(['group', 'entity', 'entityClass']);
        $resolver->setNormalizers([
            'entity' => function (Options $options, $value) {
                if (empty($value) && !$options['entityClass']) {
                    throw new LogicException(
                        'entity or entityClass must be provided'
                    );
                }

                return $value;
            }
        ]);
    }

    /**
     * @todo: data fetch should be performed automatically from registered data provider
     *
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['actions'] = $block->getData()->get('actions');
        $view->vars['dialogRoute'] = $options['dialogRoute'];
        $view->vars['executionRoute'] = $options['executionRoute'];

        $entity = $options['entity'];
        if ($entity && is_object($entity)) {
            $view->vars['entityClass'] = ClassUtils::getClass($entity);
            $view->vars['entityId'] = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        } else {
            $view->vars['entityClass'] = ClassUtils::getRealClass($options['entityClass']);
            $view->vars['entityId'] = null;
        }
    }
}

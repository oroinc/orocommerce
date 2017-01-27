<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SlugPrototypesWithRedirect;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugPrototypesWithRedirectType extends AbstractType
{
    const NAME = 'oro_redirect_slug_prototypes_with_redirect';
    const CREATE_REDIRECT_OPTION_NAME = 'createRedirect';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
    public function getBlockPrefix()
    {
        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'slugPrototypes',
                LocalizedSlugType::NAME,
                [
                    'required' => false,
                    'options'  => ['constraints' => [new UrlSafe()]],
                    'source_field' => $options['source_field'],
                    'label' => false,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        if ($this->isRedirectConfirmationEnabled($event->getForm()->getConfig()->getOptions())
            && $this->isNotEmptyCollection($event->getData())
        ) {
            $event->getForm()->add(
                self::CREATE_REDIRECT_OPTION_NAME,
                CheckboxType::class,
                [
                    'label' => 'oro.redirect.confirm_slug_change.checkbox_label',
                    'data' => true,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'create_redirect_enabled' => false,
            'data_class' => SlugPrototypesWithRedirect::class,
        ]);
        $resolver->setRequired('source_field');
    }

    /**
     * @param mixed $data
     * @return bool
     */
    protected function isNotEmptyCollection($data)
    {
        return $data instanceof Collection && !$data->isEmpty();
    }

    /**
     * @param array|\ArrayAccess $options
     * @return bool
     */
    protected function isRedirectConfirmationEnabled($options)
    {
        return $this->configManager->get('oro_redirect.redirect_generation_strategy') === Configuration::STRATEGY_ASK;
    }
}

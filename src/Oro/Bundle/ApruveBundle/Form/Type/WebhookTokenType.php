<?php

namespace Oro\Bundle\ApruveBundle\Form\Type;

use Oro\Bundle\SecurityBundle\Generator\RandomTokenGeneratorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebhookTokenType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_apruve_webhook_token';

    /**
     * @var RandomTokenGeneratorInterface
     */
    private $generator;

    /**
     * @param RandomTokenGeneratorInterface $randomTokenGenerator
     */
    public function __construct(RandomTokenGeneratorInterface $randomTokenGenerator)
    {
        $this->generator = $randomTokenGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'empty_data' => function () {
                return $this->generator->generateToken();
            },
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }
}

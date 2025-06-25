<?php

namespace Plugin\TestPlugin\Form\Type\Admin;

use Plugin\TestPlugin\Entity\Config;
use Plugin\TestPlugin\Service\ConfigService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    /**
     * @var ConfigService
     */
    private $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(Config::CONFIG_KEY_IS_ENABLED, CheckboxType::class, [
            'mapped' => false,
            'required' => false,
        ]);

        // Form event listeners

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            $form->get(Config::CONFIG_KEY_IS_ENABLED)->setData($this->configService->isPluginEnabled());
        });

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }

}

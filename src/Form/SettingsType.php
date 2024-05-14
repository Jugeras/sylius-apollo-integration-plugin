<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SettingsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('excel', FileType::class, [
                'label' => 'printplius_sylius_apollo_integration.ui.excel',
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10240k',
                        'mimeTypes' => [
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ]
                    ])
                ],
                'attr' => [
                    'accept' => '.xls, .xlsx',
                ]
            ])
            ->add('images', FileType::class, [
                'label' => 'printplius_sylius_apollo_integration.ui.images',
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10240k',
                        'mimeTypes' => [
                            'application/zip',
                            'application/octet-stream '
                        ]
                    ])
                ],
                'attr' => [
                    'accept' => '.zip',
                ]
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}

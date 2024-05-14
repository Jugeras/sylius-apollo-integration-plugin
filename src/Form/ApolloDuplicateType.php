<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Form;

use PrintPlius\SyliusApolloIntegrationPlugin\Entity\ApolloDuplicate;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApolloDuplicateType extends AbstractResourceType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('code', TextType::class, [
                'label' => 'printplius_sylius_apollo_integration.ui.code',
                'disabled' => true
            ])
            ->add('importManufacturer', TextType::class, [
                'label' => 'printplius_sylius_apollo_integration.ui.import_manufacturer',
                'disabled' => true
            ])
            ->add('productManufacturer', TextType::class, [
                'label' => 'printplius_sylius_apollo_integration.ui.product_manufacturer',
                'disabled' => true
            ])
            ->add('newCode', TextType::class, [
                'label' => 'printplius_sylius_apollo_integration.ui.new_code',
            ])
            ->add('newManufacturer', TextType::class, [
                'label' => 'printplius_sylius_apollo_integration.ui.new_manufacturer',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApolloDuplicate::class,
        ]);
    }
}

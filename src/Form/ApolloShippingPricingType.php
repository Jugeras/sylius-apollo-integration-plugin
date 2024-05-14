<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Form;

use PrintPlius\SyliusApolloIntegrationPlugin\Entity\ApolloShippingPricing;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApolloShippingPricingType extends AbstractResourceType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('from', NumberType::class, [
                'scale' => 3,
                'label' => 'printplius_sylius_apollo_integration.ui.from',
            ])
            ->add('to', NumberType::class, [
                'scale' => 3,
                'label' => 'printplius_sylius_apollo_integration.ui.to',
            ])
            ->add('price', NumberType::class, [
                'scale' => 2,
                'label' => 'printplius_sylius_apollo_integration.ui.price',
            ])
        ;

        $builder->get('price')
            ->addModelTransformer(new CallbackTransformer(
                function (?int $price): float {
                    if ($price)
                        return $price/100;
                    return 0;
                },
                function (float $price): int {
                    return (int)round($price*100);
                }
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApolloShippingPricing::class,
        ]);
    }
}

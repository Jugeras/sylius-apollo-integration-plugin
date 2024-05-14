<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ApolloShippingPricingRepository extends EntityRepository implements ApolloShippingPricingRepositoryInterface
{

    public function __construct(EntityManagerInterface $em, ClassMetadata $apolloShippingPricing)
    {
        parent::__construct($em, $apolloShippingPricing);
    }

    public function findByWeight($weight)
    {
        return $this->createQueryBuilder('apollo')
            ->andWhere('apollo.from < :weight')
            ->andWhere('apollo.to >= :weight')
            ->setParameter('weight', $weight)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

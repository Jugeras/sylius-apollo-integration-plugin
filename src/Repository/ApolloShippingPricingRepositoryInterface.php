<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Repository;

use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ApolloShippingPricingRepositoryInterface extends RepositoryInterface
{
    public function findByWeight($weight);
}

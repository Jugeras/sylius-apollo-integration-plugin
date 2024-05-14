<?php

declare(strict_types=1);

namespace Tests\PrintPlius\SyliusApolloIntegrationPlugin\Application\Entity\Addressing;

use Doctrine\ORM\Mapping as ORM;
use PrintPlius\SyliusB2BPlugin\Entity\Addressing\CompanyAwareInterface;
use PrintPlius\SyliusB2BPlugin\Entity\Addressing\CompanyAwareTrait;
use Sylius\Component\Core\Model\Address as BaseAddress;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_address")
 */
class Address extends BaseAddress implements CompanyAwareInterface
{
    use CompanyAwareTrait;
}

<?php

declare(strict_types=1);

namespace Tests\PrintPlius\SyliusApolloIntegrationPlugin\Application\Entity\Customer;

use Doctrine\ORM\Mapping as ORM;
use PrintPlius\SyliusB2BPlugin\Entity\Customer\BillingAwareInterface;
use PrintPlius\SyliusB2BPlugin\Entity\Customer\BillingAwareTrait;
use Sylius\Component\Core\Model\Customer as BaseCustomer;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_customer")
 */
class Customer extends BaseCustomer implements BillingAwareInterface
{
    use BillingAwareTrait;
}

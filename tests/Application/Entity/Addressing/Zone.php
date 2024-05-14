<?php

declare(strict_types=1);

namespace Tests\PrintPlius\SyliusApolloIntegrationPlugin\Application\Entity\Addressing;

use Doctrine\ORM\Mapping as ORM;
use PrintPlius\SyliusB2BPlugin\Entity\Addressing\EuropeVatAwareInterface;
use PrintPlius\SyliusB2BPlugin\Entity\Addressing\EuropeVatAwareTrait;
use Sylius\Component\Addressing\Model\Zone as BaseZone;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_zone")
 */
class Zone extends BaseZone implements EuropeVatAwareInterface
{
    use EuropeVatAwareTrait;
}

<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use PrintPlius\SyliusApolloIntegrationPlugin\Repository\ApolloShippingPricingRepository;

/**
 * @ORM\Entity(repositoryClass=ApolloShippingPricingRepository::class)
 * @UniqueEntity(fields={"from", "to"}, message="printplius_sylius_apollo_integration.error.apollo_shipping_pricing.range.unique")
 * @ORM\Table(name="printplius_apollo_shipping_pricing")
 */
class ApolloShippingPricing implements ResourceInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="decimal", scale=3, precision=10, name="`from`")
     * @Assert\NotBlank(message="printplius_sylius_apollo_integration.error.apollo_shipping_pricing.from.not_blank")
     * @Assert\PositiveOrZero (
     *     message = "printplius_sylius_apollo_integration.error.apollo_shipping_pricing.from.positive_or_zero",
     * )
     */
    protected $from;

    /**
     * @ORM\Column(type="decimal", scale=3, precision=10, name="`to`")
     * @Assert\NotBlank(message="printplius_sylius_apollo_integration.error.apollo_shipping_pricing.to.not_blank")
     * @Assert\PositiveOrZero (
     *     message = "printplius_sylius_apollo_integration.error.apollo_shipping_pricing.to.positive_or_zero",
     * )
     */
    protected $to;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(message="printplius_sylius_apollo_integration.error.apollo_shipping_pricing.price.not_blank")
     * @Assert\PositiveOrZero (
     *     message = "printplius_sylius_apollo_integration.error.apollo_shipping_pricing.price.positive_or_zero",
     * )
     */
    protected $price;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param mixed $from
     */
    public function setFrom($from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param mixed $to
     */
    public function setTo($to): self
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }
}

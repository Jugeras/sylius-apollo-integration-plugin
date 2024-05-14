<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use PrintPlius\SyliusApolloIntegrationPlugin\Repository\ApolloDuplicateRepository;

/**
 * @ORM\Entity(repositoryClass=ApolloDuplicateRepository::class)
 * @ORM\Table(name="printplius_apollo_duplicate", indexes={
 *  @ORM\Index(name="code_idx", columns={"code"})
 * })
 *
 */
class ApolloDuplicate implements ResourceInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, name="`code`")
     */
    protected $code;

    /**
     * @ORM\Column(type="string", length=255, name="`import_manufacturer`")
     */
    protected $importManufacturer;

    /**
     * @ORM\Column(type="string", length=255, name="`product_manufacturer`")
     */
    protected $productManufacturer;

    /**
     * @ORM\Column(type="string", length=255, name="`new_code`", nullable=true)
     */
    protected $newCode;

    /**
     * @ORM\Column(type="string", length=255, name="`new_manufacturer`", nullable=true)
     */
    protected $newManufacturer;

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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code): void
    {
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getImportManufacturer()
    {
        return $this->importManufacturer;
    }

    /**
     * @param mixed $importManufacturer
     */
    public function setImportManufacturer($importManufacturer): void
    {
        $this->importManufacturer = $importManufacturer;
    }

    /**
     * @return mixed
     */
    public function getProductManufacturer()
    {
        return $this->productManufacturer;
    }

    /**
     * @param mixed $productManufacturer
     */
    public function setProductManufacturer($productManufacturer): void
    {
        $this->productManufacturer = $productManufacturer;
    }

    /**
     * @return mixed
     */
    public function getNewCode()
    {
        return $this->newCode;
    }

    /**
     * @param mixed $newCode
     */
    public function setNewCode($newCode): void
    {
        $this->newCode = $newCode;
    }

    /**
     * @return mixed
     */
    public function getNewManufacturer()
    {
        return $this->newManufacturer;
    }

    /**
     * @param mixed $newManufacturer
     */
    public function setNewManufacturer($newManufacturer): void
    {
        $this->newManufacturer = $newManufacturer;
    }


}

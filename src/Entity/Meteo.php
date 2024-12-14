<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\MeteoRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: MeteoRepository::class)]
// #[ApiResource(
//     description: 'Les prévisions météos',
//     operations: [
//         new Get(),
//         new GetCollection(),
//     ],
//     denormalizationContext: ['groups' => ['meteo:write']],

// )]

class Meteo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min:3, minMessage:"Le nom de la ville doit faire un minimum 3 caractères")]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min:5, max:5, minMessage:"Le code postal doit faire 5 caractères")]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $forecast = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getForecast(): ?string
    {
        return $this->forecast;
    }

    public function setForecast(?string $forecast): static
    {
        $this->forecast = $forecast;

        return $this;
    }
}

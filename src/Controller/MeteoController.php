<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



class MeteoController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('api/meteo/{ville?}', name: 'meteo', methods:['GET'])]
    public function getMeteo(HttpClientInterface $httpClient, ?string $ville): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$ville) {
           if(!$user instanceof User || !$user->getCity()) {
            return new JsonResponse(['error' => 'Ville non spécifié'], 400);
            }

            $ville = $user->getCity();
        }

        $apiUrl = "https://api.openweathermap.org/data/2.5/weather?q={$ville}&appid=c959e41603ee00f03a11176562032291&units=metric&lang=fr";

        $response = $httpClient->request(
            'GET', 
            $apiUrl);
        $data = $response->toArray();

        // Personnaliser la réponse si nécessaire
        $result = [
            'city' => $data['name'],
            'temperature' => $data['main']['temp'],
            'ressenti' => $data['main']['feels_like'],
            'description' => $data['weather'][0]['description'],
            'vitesse du vent' => $data['wind']['speed'],
        ];

        return new JsonResponse($result);

    }
}

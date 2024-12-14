<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Meteo;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class MeteoController extends AbstractController
{
    #[Route('api/meteo/{city?}', name: 'meteo', methods:['GET'])] 
    #[OA\Response(
        response:200,
        description:'Retourne la météo de la ville donnée, si aucune ville saisie, la ville de l\'utilisateur est utilisée',
        content: new OA\JsonContent(
           type:'array',
           items: new OA\Items(ref: new Model(type:Meteo::class))
        )
    )]
    #[OA\Tag(name:'Meteo')]   
    /**
     * getMeteo
     *
     * @param  mixed $httpClient
     * @param  mixed $city
     * @param  mixed $cachePool
     * @return JsonResponse
     */
    public function getMeteo(HttpClientInterface $httpClient, ?string $city, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $user = $this->getUser();

        if (!$city) {
           if(!$user instanceof User || !$user->getCity()) {
            return new JsonResponse(['error' => 'Ville non spécifié'], 400);
            }

            $city = $user->getCity();
        }

        $cacheKey = sprintf('weather_data_%s', strtolower($city));
        
        $data = $cachePool->get($cacheKey, function ($cacheItem) use ($httpClient, $city) {
            $cacheItem->expiresAfter(1800); // Expire après 30 minutes
            $apiUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid=c959e41603ee00f03a11176562032291&units=metric&lang=fr";

            $response = $httpClient->request('GET', $apiUrl);
            $data = $response->toArray();

            // Personnaliser la réponse si nécessaire
            return [
                'city' => $data['name'],
                'temperature' => $data['main']['temp'],
                'ressenti' => $data['main']['feels_like'],
                'description' => $data['weather'][0]['description'],
                'vitesse du vent' => $data['wind']['speed'],
            ];
        });

        return new JsonResponse($data);
    }
}

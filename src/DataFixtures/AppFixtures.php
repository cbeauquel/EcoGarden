<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Month;
use App\Entity\Advice;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {        
        // Création d'un user "normal"
        $user = new User();
        $user->setEmail("user@ecogarden.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "trucmuche"));
        $user->setCity("Tours");
        $user->setPostalCode("37000");
        $manager->persist($user);
        
        // Création d'un user admin
        $userAdmin = new User();
        $userAdmin->setEmail("admin@ecogarden.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "trucmuche"));
        $userAdmin->setCity("Bordeaux");
        $userAdmin->setPostalCode("33000");

        $manager->persist($userAdmin);

        // Création de la liste de mois
        $listMonth = [];
        for ($i = 1; $i < 13; $i++) {
            $month = new Month();
            $month->setNumber($i);
            $manager->persist($month);

            $listMonth[] = $month;
        }

        // Création de la liste de conseils
        $listAdvice = [];
        for ($i = 1; $i < 20; $i++) {
            $advice = new Advice();
            $advice->setContent("Conseil N°:" . $i);
            $advice->addMonth($listMonth[array_rand($listMonth)]);
            $manager->persist($advice);

            $listAdvice[] = $advice;
        }

        $manager->flush();
    }
}
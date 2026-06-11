<?php

namespace App\DataFixtures;

use App\Entity\Animal;
use App\Entity\Booking;
use App\Entity\City;
use App\Entity\PetSitterProfile;
use App\Entity\Region;
use App\Entity\Review;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // --- Régions du Maroc (avec quelques villes par région) ---
        $geo = [
            'Casablanca-Settat' => ['الدار البيضاء سطات', [
                ['Casablanca', 'الدار البيضاء', 33.5731, -7.5898],
                ['Mohammedia', 'المحمدية', 33.6861, -7.3829],
                ['Settat', 'سطات', 33.0010, -7.6160],
                ['El Jadida', 'الجديدة', 33.2316, -8.5007],
            ]],
            'Rabat-Salé-Kénitra' => ['الرباط سلا القنيطرة', [
                ['Rabat', 'الرباط', 34.0209, -6.8416],
                ['Salé', 'سلا', 34.0531, -6.7985],
                ['Kénitra', 'القنيطرة', 34.2610, -6.5802],
                ['Témara', 'تمارة', 33.9287, -6.9067],
            ]],
            'Marrakech-Safi' => ['مراكش آسفي', [
                ['Marrakech', 'مراكش', 31.6295, -7.9811],
                ['Safi', 'آسفي', 32.2994, -9.2372],
                ['Essaouira', 'الصويرة', 31.5085, -9.7595],
            ]],
            'Fès-Meknès' => ['فاس مكناس', [
                ['Fès', 'فاس', 34.0181, -5.0078],
                ['Meknès', 'مكناس', 33.8935, -5.5473],
                ['Ifrane', 'إفران', 33.5333, -5.1100],
            ]],
            'Tanger-Tétouan-Al Hoceïma' => ['طنجة تطوان الحسيمة', [
                ['Tanger', 'طنجة', 35.7595, -5.8340],
                ['Tétouan', 'تطوان', 35.5785, -5.3684],
                ['Al Hoceïma', 'الحسيمة', 35.2517, -3.9372],
            ]],
            'Souss-Massa' => ['سوس ماسة', [
                ['Agadir', 'أكادير', 30.4278, -9.5981],
                ['Taroudant', 'تارودانت', 30.4703, -8.8766],
            ]],
            'Oriental' => ['الشرق', [
                ['Oujda', 'وجدة', 34.6814, -1.9086],
                ['Nador', 'الناظور', 35.1688, -2.9335],
            ]],
            'Béni Mellal-Khénifra' => ['بني ملال خنيفرة', [
                ['Béni Mellal', 'بني ملال', 32.3373, -6.3498],
                ['Khénifra', 'خنيفرة', 32.9394, -5.6681],
            ]],
        ];

        /** @var City[] $cities */
        $cities = [];
        foreach ($geo as $regionName => [$regionAr, $cityList]) {
            $region = (new Region())->setName($regionName)->setNameAr($regionAr);
            $manager->persist($region);
            foreach ($cityList as [$cn, $car, $lat, $lng]) {
                $city = (new City())
                    ->setName($cn)->setNameAr($car)
                    ->setRegion($region)->setLatitude($lat)->setLongitude($lng);
                $manager->persist($city);
                $cities[$cn] = $city;
            }
        }

        // --- Services proposés ---
        $servicesData = [
            ['home_boarding', 'Garde à domicile (chez le gardien)', 'الإيواء في المنزل', 'mdi-home-heart'],
            ['pet_sitting', 'Garde chez le propriétaire', 'الرعاية في منزل المالك', 'mdi-paw'],
            ['dog_walking', 'Promenade de chien', 'تمشية الكلاب', 'mdi-walk'],
            ['day_care', 'Garderie de jour', 'الحضانة النهارية', 'mdi-weather-sunny'],
            ['drop_in_visit', 'Visite à domicile', 'زيارة منزلية', 'mdi-map-marker-check'],
        ];
        /** @var Service[] $services */
        $services = [];
        foreach ($servicesData as [$code, $name, $nameAr, $icon]) {
            $s = (new Service())->setCode($code)->setName($name)->setNameAr($nameAr)->setIcon($icon);
            $manager->persist($s);
            $services[$code] = $s;
        }

        // --- Admin ---
        $admin = (new User())
            ->setEmail('admin@daripets.ma')
            ->setFirstName('Admin')->setLastName('Animaute')
            ->setType(User::TYPE_OWNER)
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        // --- Propriétaire de démo ---
        $owner = (new User())
            ->setEmail('proprio@daripets.ma')
            ->setFirstName('Yasmine')->setLastName('Bennani')
            ->setType(User::TYPE_OWNER)
            ->setPhone('+212600000001')
            ->setCity($cities['Casablanca']);
        $owner->setPassword($this->hasher->hashPassword($owner, 'password'));
        $manager->persist($owner);

        $owner->setAvatar('https://i.pravatar.cc/300?img=47');

        $rex = (new Animal())->setOwner($owner)->setName('Rex')->setType(Animal::TYPE_DOG)
            ->setBreed('Berger allemand')->setAge(3)
            ->setPhoto('https://placedog.net/400/300?id=12');
        $manager->persist($rex);
        $minou = (new Animal())->setOwner($owner)->setName('Minou')->setType(Animal::TYPE_CAT)
            ->setBreed('Européen')->setAge(2)
            ->setPhoto('https://cataas.com/cat?width=400&height=300');
        $manager->persist($minou);

        // --- Gardiens de démo ---
        $sittersData = [
            ['karim@daripets.ma', 'Karim', 'El Alaoui', 'Casablanca', "Passionné d'animaux depuis toujours", 150, 80, 5, 'dog,cat', ['home_boarding', 'dog_walking'], 4.8, 24, true],
            ['salma@daripets.ma', 'Salma', 'Idrissi', 'Rabat', "Vétérinaire de formation, j'adore les chats", 200, 100, 8, 'cat,dog,rodent', ['pet_sitting', 'drop_in_visit'], 4.9, 41, true],
            ['youssef@daripets.ma', 'Youssef', 'Tazi', 'Marrakech', 'Grand jardin pour vos compagnons', 120, 60, 2, 'dog', ['home_boarding', 'day_care', 'dog_walking'], 4.5, 12, false],
            ['fatima@daripets.ma', 'Fatima', 'Zahra', 'Fès', 'Calme et patiente avec tous les animaux', 130, 70, 4, 'cat,bird', ['pet_sitting', 'drop_in_visit'], 4.7, 18, true],
            ['amine@daripets.ma', 'Amine', 'Benjelloun', 'Tanger', 'Disponible 7j/7 pour vos animaux', 110, 55, 3, 'dog,cat', ['dog_walking', 'day_care'], 4.3, 7, false],
            ['nadia@daripets.ma', 'Nadia', 'Cherkaoui', 'Agadir', 'Maison près de la plage, balades quotidiennes', 140, 75, 6, 'dog,cat', ['home_boarding', 'dog_walking', 'drop_in_visit'], 4.6, 15, true],
        ];

        // Avatars photo (pravatar) attribués dans l'ordre des gardiens
        $avatarIds = [11, 32, 59, 26, 68, 44];

        /** @var array<int, array{0: User, 1: int, 2: array<string>}> $sitterEntities  [user, dailyRate, serviceCodes] */
        $sitterEntities = [];
        foreach ($sittersData as $idx => [$email, $fn, $ln, $cityName, $bio, $daily, $hourly, $exp, $animals, $svcCodes, $rating, $reviews, $verified]) {
            $u = (new User())
                ->setEmail($email)->setFirstName($fn)->setLastName($ln)
                ->setType(User::TYPE_SITTER)
                ->setCity($cities[$cityName])
                ->setBio($bio)
                ->setAvatar('https://i.pravatar.cc/300?img=' . $avatarIds[$idx % count($avatarIds)])
                ->setPhone('+2126' . random_int(10000000, 99999999));
            $u->setPassword($this->hasher->hashPassword($u, 'password'));
            $manager->persist($u);
            $sitterEntities[] = [$u, $daily, $svcCodes];

            $profile = (new PetSitterProfile())
                ->setUser($u)
                ->setHeadline($bio)
                ->setDescription($bio . '. Je propose des prestations de qualité adaptées à vos compagnons à ' . $cityName . '.')
                ->setDailyRate((string) $daily)
                ->setHourlyRate((string) $hourly)
                ->setExperienceYears($exp)
                ->setAcceptedAnimalTypes($animals)
                ->setRating($rating)
                ->setReviewCount($reviews)
                ->setVerified($verified)
                ->setServiceRadius(15);
            foreach ($svcCodes as $code) {
                $profile->addService($services[$code]);
            }
            $u->setSitterProfile($profile);
            $manager->persist($profile);

            // Un avis de démo du propriétaire
            $review = (new Review())
                ->setAuthor($owner)->setTarget($u)
                ->setRating(random_int(3, 5))
                ->setComment('Excellent gardien, très attentionné avec ' . $rex->getName() . ' !');
            $manager->persist($review);
        }

        // --- Étale les dates d'inscription sur ~6 mois (pour les graphiques) ---
        $allUsers = array_merge([$admin, $owner], array_map(fn ($e) => $e[0], $sitterEntities));
        foreach ($allUsers as $i => $usr) {
            $usr->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', random_int(5, 175))));
        }

        // --- Réservations de démo réparties sur les derniers mois ---
        $statuses = [
            Booking::STATUS_COMPLETED, Booking::STATUS_COMPLETED, Booking::STATUS_PAID,
            Booking::STATUS_PAID, Booking::STATUS_ACCEPTED, Booking::STATUS_PENDING,
            Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED,
        ];
        $animalsPool = [$rex, $minou];
        for ($m = 0; $m < 40; $m++) {
            [$sitterUser, $daily, $svcCodes] = $sitterEntities[array_rand($sitterEntities)];
            $start = new \DateTimeImmutable(sprintf('-%d days', random_int(1, 170)));
            $nights = random_int(1, 7);
            $end = $start->modify("+{$nights} days");
            $status = $statuses[array_rand($statuses)];

            $booking = (new Booking())
                ->setOwner($owner)
                ->setSitter($sitterUser)
                ->setService($services[$svcCodes[array_rand($svcCodes)]])
                ->setAnimal($animalsPool[array_rand($animalsPool)])
                ->setStartDate($start)
                ->setEndDate($end)
                ->setStatus($status)
                ->setTotalPrice(number_format($daily * $nights, 2, '.', ''))
                ->setCreatedAt($start->modify('-' . random_int(1, 10) . ' days'));
            $booking->applyCommission(0.15);
            $manager->persist($booking);
        }

        $manager->flush();
    }
}

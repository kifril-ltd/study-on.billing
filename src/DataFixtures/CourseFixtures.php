<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            [
                'code' => 'PPBIB',
                'type' => 2,
                'price' => 2000,
            ],
            [
                'code' => 'PPBI',
                'type' => 1,
                'price' => 2000,
            ],
            [
                'code' => 'PPBI2',
                'type' => 3,
                'price' => 2000,
            ],
            [
                'code' => 'MSCB',
                'type' => 2,
                'price' => 1000,
            ],
            [
                'code' => 'MSC',
                'type' => 3,
                'price' => 1000,
            ],
            [
                'code' => 'CAMPB',
                'type' => 2,
                'price' => 3000,
            ],
            [
                'code' => 'CAMP',
                'type' => 1,
                'price' => 3000,
            ],
        ];

        foreach ($courses as $course) {
            $newCourse = new Course();
            $newCourse->setCode($course['code']);
            $newCourse->setType($course['type']);
            if (isset($course['price'])) {
                $newCourse->setPrice($course['price']);
            }
            $manager->persist($newCourse);
        }
        $manager->flush();
    }
}

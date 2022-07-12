<?php

namespace App\Dto\Response\Transfromer;

use App\Dto\Response\CourseDto;
use App\Dto\Response\UserAuthDto;
use App\Entity\Course;

class CourseResponseTransformer
{
    public static function transformFromObjects(array $courses): array
    {
        $coursesDto = [];

        /** @var Course $course */
        foreach ($courses as $course) {
            $dto = new CourseDto();
            $dto->code = $course->getCode();
            $dto->title = $course->getTitle();
            $dto->type = $course->getType();
            $dto->price = $course->getPrice();

            $coursesDto[] = $dto;
        }

        return $coursesDto;
    }

    public static function transformFromObject(Course $course): CourseDto
    {
        $dto = new CourseDto();
        $dto->code = $course->getCode();
        $dto->title = $course->getTitle();
        $dto->type = $course->getType();
        $dto->price = $course->getPrice();

        return $dto;
    }
}
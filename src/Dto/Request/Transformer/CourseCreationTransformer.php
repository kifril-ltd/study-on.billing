<?php

namespace App\Dto\Request\Transformer;

use App\Dto\Request\CourseCreationRequestDto;
use App\Dto\Response\CourseDto;
use App\Dto\Response\UserAuthDto;
use App\Entity\Course;

class CourseCreationTransformer
{
        public static function transformToObject(CourseCreationRequestDto $courseCreationDto): Course
    {
        $course = new Course();
        $course->setCode($courseCreationDto->code);
        $course->setTitle($courseCreationDto->title);
        $course->setType($courseCreationDto->type);
        $course->setPrice($courseCreationDto->price);

        return $course;
    }
}
<?php

namespace App\Dto\Request;

use JMS\Serializer\Annotation as Serialization;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     title="CourseCreationRequestDto",
 *     description="CourseCourseCreationDTO"
 * )
 * Class CourseDTO
 * @package App\Dto
 */
class CourseCreationRequestDto
{

    /**
     * @OA\Property(
     *     format="string",
     *     title="type",
     *     description="Тип курса",
     *     example="buy"
     * )
     */
    #[Serialization\Type("string")]
    public string $type;

    /**
     * @OA\Property(
     *     format="string",
     *     title="title",
     *     description="Название курса",
     *     example="Программирование"
     * )
     */
    #[Serialization\Type("string")]
    public string $title;

    /**
     * @OA\Property(
     *     format="string",
     *     title="code",
     *     description="Код курса",
     *     example="MLSADKLD13213KSDMDNVM35"
     * )
     */
    #[Serialization\Type("string")]
    public string $code;

    /**
     * @OA\Property(
     *     format="float",
     *     title="price",
     *     description="Стоимость курса",
     *     example="15000"
     * )
     */
    #[Serialization\Type("float")]
    public float $price;
}

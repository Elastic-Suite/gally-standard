<?php
/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Job\Entity\Job;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use Doctrine\ORM\Mapping as ORM;
use Gally\User\Constant\Role;
use Symfony\Component\HttpFoundation\File\File as SfFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['job_file:read']],
    types: ['JobFile'],
    outputFormats: ['jsonld' => ['application/ld+json']],
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                    ])
                )
            ),
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ),
    ],
    shortName: 'JobFile'
)]
class File
{
    #[Groups(['job_file:read'])]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'job', fileNameProperty: 'filePath')]
    public ?SfFile $file = null;

    #[ApiProperty(writable: false)]
    #[Groups(['job_file:read'])]
    public ?string $filePath;

    public function getId(): ?int
    {
        return $this->id;
    }
}

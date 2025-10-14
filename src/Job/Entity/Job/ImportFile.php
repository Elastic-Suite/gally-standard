<?php
// api/src/Entity/MediaObject.php

namespace Gally\Job\Entity\Job;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use Doctrine\ORM\Mapping as ORM;
use Gally\Job\Entity\Job;
use Gally\User\Constant\Role;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['job_import_file:read']],
    types: ['https://schema.org/JobImportFile'],
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
                                        'format' => 'binary'
                                    ]
                                ]
                            ]
                        ]
                    ])
                )
            ),
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        )
    ],
    shortName: 'JobImportFile'
)]
class ImportFile
{
    private ?int $id = null;

    #[ApiProperty(types: ['https://schema.org/contentUrl'], writable: false)]
    #[Groups(['job_import_file:read'])]
    public ?string $contentUrl = null;

    #[Vich\UploadableField(mapping: 'job_import', fileNameProperty: 'filePath')]
    #[Assert\NotNull] // todo: déplacer validation dans un fichier de validation en yaml pour que l'on puisse l'étendre
    public ?File $file = null;

    #[ApiProperty(writable: false)]
    public ?string $filePath = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

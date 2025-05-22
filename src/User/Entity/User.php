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

namespace Gally\User\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Gally\Doctrine\Filter\JsonFilter;
use Gally\User\Constant\Role;
use Gally\User\State\UserProcessor;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Put(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Patch(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Delete(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Post(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
    ],
    graphQlOperations: [
        new Query(name: 'item_query', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new QueryCollection(name: 'collection_query', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'create', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'update', security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Mutation(name: 'delete', security: "is_granted('" . Role::ROLE_ADMIN . "')")],
    processor: UserProcessor::class,
    denormalizationContext: ['groups' => ['user:write']],
    normalizationContext: ['groups' => ['user:read']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['firstName' => 'ipartial', 'lastName' => 'ipartial', 'email' => 'ipartial'])]
#[ApiFilter(filterClass: BooleanFilter::class, properties: ['isActive'])]
#[ApiFilter(filterClass: JsonFilter::class, properties: ['roles' => null])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups('user:read')]
    private int $id;

    #[ApiProperty(
        required: true,
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'First name',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 10,
                    'form' => [
                        'placeholder' => 'First name',
                        'fieldset' => 'general',
                        'position' => 20,
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['user:read', 'user:write'])]
    private string $firstName;

    #[ApiProperty(
        required: true,
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Last name',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 20,
                    'form' => [
                        'placeholder' => 'Last name',
                        'fieldset' => 'general',
                        'position' => 30,
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['user:read', 'user:write'])]
    private string $lastName;

    #[ApiProperty(
        required: true,
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'E-mail',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 30,
                    'input' => 'email',
                    'form' => [
                        'placeholder' => 'E-mail',
                        'fieldset' => 'general',
                        'position' => 40,
                        'depends' => [
                            'type' => 'enabled',
                            'conditions' => [
                                ['field' => 'id', 'value' => null],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['user:read', 'user:write'])]
    private string $email;

    #[ApiProperty(
        required: true,
        types: 'array',
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Role(s)',
                    'range' => 'xmls:array',
                ],
                'gally' => [
                    'infoTooltip' => 'If you select the value ROLE_ADMIN, all roles will be selected automatically, because ROLE_ADMIN includes all roles.',
                    'visible' => true,
                    'editable' => false,
                    'position' => 40,
                    'input' => 'select',
                    'options' => [
                        'values' => [
                            ['value' => Role::ROLE_ADMIN, 'label' => 'Administrator'],
                            ['value' => Role::ROLE_CONTRIBUTOR, 'label' => 'Contributor'],
                        ],
                    ],
                    'form' => [
                        'placeholder' => 'Role(s)',
                        'fieldset' => 'general',
                        'position' => 60,
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    #[ApiProperty(
        required: true,
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Enable',
                ],
                'gally' => [
                    'visible' => true,
                    'editable' => false,
                    'position' => 60,
                    'form' => [
                        'defaultValue' => true,
                        'fieldset' => 'general',
                        'position' => 10,
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['user:read', 'user:write'])]
    private bool $isActive = true;

    private string $password = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * The function getUsername has been replaced by getUserIdentifier in sf 6,
     * but lexik/jwt-authentication-bundle still use the getUsername function.
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = Role::ROLE_CONTRIBUTOR;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    #[ApiProperty(
        extraProperties: [
            'hydra:supportedProperty' => [
                'hydra:property' => [
                    'rdfs:label' => 'Password',
                ],
                'gally' => [
                    'infoTooltip' => 'An email will be sent to the user allowing them to set their password.',
                    'visible' => false,
                    'editable' => false,
                    'position' => 30,
                    'input' => 'password',
                    'form' => [
                        'visible' => true,
                        'placeholder' => '********',
                        'fieldset' => 'general',
                        'position' => 50,
                        'depends' => [
                            // A "depends" without conditions allows to disable a field.
                            'type' => 'enabled',
                            'conditions' => [],
                        ],
                    ],
                ],
            ],
        ],
    )]
    #[Groups(['user:read'])]
    public function getDummyPassword(): string
    {
        return '********';
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * Get valid role values.
     *
     * @return string[]
     */
    public static function getValidRole(): array
    {
        return Role::ROLES;
    }
}

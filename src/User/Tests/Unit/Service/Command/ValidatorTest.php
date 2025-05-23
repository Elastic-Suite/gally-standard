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

namespace Gally\User\Tests\Unit\Service\Command;

use Gally\Test\AbstractTestCase;
use Gally\User\Constant\Role;
use Gally\User\Service\Command\QuestionBuilder;
use Gally\User\Service\Command\Validator;

class ValidatorTest extends AbstractTestCase
{
    private static QuestionBuilder $cmdQuestionBuilder;
    private static Validator $cmdValidator;
    private static string $userEmail = 'admin@test.com';
    private static string $fakeUserEmail = 'fake_admin@test.com';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$cmdQuestionBuilder = static::getContainer()->get(QuestionBuilder::class);
        self::$cmdValidator = static::getContainer()->get(Validator::class);
    }

    public function testUserEmailExists(): void
    {
        $question = self::$cmdQuestionBuilder->getQuestion('Email:');

        self::$cmdValidator->userEmailExists($question);
        $validatorFunction = $question->getValidator();
        $this->assertEquals(self::$userEmail, $validatorFunction(self::$userEmail));

        self::$cmdValidator->userEmailExists($question, true);
        $validatorFunction = $question->getValidator();
        $this->assertNull($validatorFunction(null));
    }

    /**
     * @dataProvider errorUserEmailExistsDataProvider
     */
    public function testErrorUserEmailExists(?string $value, array $errors): void
    {
        $question = self::$cmdQuestionBuilder->getQuestion('Email:');
        self::$cmdValidator->userEmailExists($question);
        $validatorFunction = $question->getValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(implode(\PHP_EOL, $errors));
        $validatorFunction($value);
    }

    public function errorUserEmailExistsDataProvider(): array
    {
        return [
            [
                null,
                ['This field cannot be empty.'],
            ],
            [
                '',
                ['This field cannot be empty.'],
            ],
            [
                self::$fakeUserEmail,
                ['This user does not exist.'],
            ],
        ];
    }

    public function testUserEmailNotExists(): void
    {
        $question = self::$cmdQuestionBuilder->getQuestion('Email:');

        self::$cmdValidator->userEmailNotExists($question);
        $validatorFunction = $question->getValidator();
        $this->assertEquals(self::$fakeUserEmail, $validatorFunction(self::$fakeUserEmail));

        self::$cmdValidator->userEmailNotExists($question, true);
        $validatorFunction = $question->getValidator();
        $this->assertNull($validatorFunction(null));
    }

    /**
     * @dataProvider errorUserEmailNotExistsDataProvider
     */
    public function testErrorEmailNotExists(?string $value, array $errors): void
    {
        $question = self::$cmdQuestionBuilder->getQuestion('Email:');
        self::$cmdValidator->userEmailNotExists($question);
        $validatorFunction = $question->getValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(implode(\PHP_EOL, $errors));
        $validatorFunction($value);
    }

    public function errorUserEmailNotExistsDataProvider(): array
    {
        return [
            [
                null,
                ['This field cannot be empty.'],
            ],
            [
                '',
                ['This field cannot be empty.'],
            ],
            [
                self::$userEmail,
                ['This user already exists.'],
            ],
        ];
    }

    public function testNotBlank(): void
    {
        $question = self::$cmdQuestionBuilder->getQuestion('Email:');

        self::$cmdValidator->notBlank($question);
        $validatorFunction = $question->getValidator();
        $this->assertEquals(self::$userEmail, $validatorFunction(self::$userEmail));

        self::$cmdValidator->notBlank($question, true);
        $validatorFunction = $question->getValidator();
        $this->assertNull($validatorFunction(null));
    }

    /**
     * @dataProvider errorUserNotBlankDataProvider
     */
    public function testErrorNotBlank(?string $value, array $errors): void
    {
        $question = self::$cmdQuestionBuilder->getQuestion('Email:');
        self::$cmdValidator->notBlank($question);
        $validatorFunction = $question->getValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(implode(\PHP_EOL, $errors));
        $validatorFunction($value);
    }

    public function errorUserNotBlankDataProvider(): array
    {
        return [
            [
                null,
                ['This field cannot be empty.'],
            ],
            [
                '',
                ['This field cannot be empty.'],
            ],
        ];
    }

    public function testRolesExists(): void
    {
        $question = self::$cmdQuestionBuilder->getQuestion('Role:');

        self::$cmdValidator->rolesExists($question);
        $validatorFunction = $question->getValidator();
        $this->assertEquals([Role::ROLE_CONTRIBUTOR], $validatorFunction(Role::ROLE_CONTRIBUTOR));

        self::$cmdValidator->rolesExists($question, true);
        $validatorFunction = $question->getValidator();
        $this->assertNull($validatorFunction(null));
    }

    /**
     * @dataProvider errorRolesExistsDataProvider
     */
    public function testErrorRolesExists(?string $value, array $errors): void
    {
        $question = self::$cmdQuestionBuilder->getQuestion('Email:');
        self::$cmdValidator->rolesExists($question);
        $validatorFunction = $question->getValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(implode(\PHP_EOL, $errors));
        $validatorFunction($value);
    }

    public function errorRolesExistsDataProvider(): array
    {
        return [
            [
                null,
                ['This field cannot be empty.'],
            ],
            [
                '',
                ['This field cannot be empty.'],
            ],

            [
                'ROLE_FAKE_1',
                ['The role "ROLE_FAKE_1" does not exist.'],
            ],
            [
                'ROLE_FAKE_1 ROLE_FAKE_2',
                ['The roles "ROLE_FAKE_1, ROLE_FAKE_2" do not exist.'],
            ],
        ];
    }
}

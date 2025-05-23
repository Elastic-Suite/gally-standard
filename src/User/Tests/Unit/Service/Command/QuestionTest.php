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
use Gally\User\Service\Command\QuestionBuilder;

class QuestionTest extends AbstractTestCase
{
    public function testGetQuestion(): void
    {
        $cmdQuestionBuilder = static::getContainer()->get(QuestionBuilder::class);
        $label = 'Email:';
        $question = $cmdQuestionBuilder->getQuestion($label);
        $this->assertEquals($label, $question->getQuestion());
        $this->assertTrue($question->isTrimmable());
        $this->assertEquals(3, $question->getMaxAttempts());

        $attempts = 5;
        $question = $cmdQuestionBuilder->getQuestion($label, false, $attempts);
        $this->assertFalse($question->isTrimmable());
        $this->assertEquals($attempts, $question->getMaxAttempts());
    }
}

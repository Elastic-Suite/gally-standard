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

namespace Gally\Doctrine\Dql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Custom DQL function to support: ARRAY(:param1, :param2)
 * Translates to: ARRAY[:param1, :param2]::text[].
 */
class ArrayFunction extends FunctionNode
{
    public $params = [];

    public function parse(Parser $parser): void
    {
        // Match 'ARRAY('
        $parser->match(TokenType::T_IDENTIFIER); // ARRAY
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        // First parameter
        $this->params[] = $parser->ArithmeticPrimary();

        // Handle optional additional parameters separated by commas
        while ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->params[] = $parser->ArithmeticPrimary();
        }

        // Match closing parenthesis
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        // Convert all parameters to SQL
        $args = array_map(fn ($param) => $param->dispatch($sqlWalker), $this->params);

        // Return valid SQL array syntax
        return \sprintf('ARRAY[%s]::text[]', implode(', ', $args));
    }
}

<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder;

use Asseco\JsonQueryBuilder\Config\TypesConfig;
use Asseco\JsonQueryBuilder\Types\AbstractType;

class CategorizedValues
{
    /**
     * Constants for micro-operator declaration.
     */
    const NOT = '!';
    const LIKE = '%';
    const IS_NULL = 'null';
    const IS_NOT_NULL = '!null';

    protected SearchParser $searchParser;

    public array $and = [];
    public array $andLike = [];
    public array $not = [];
    public array $notLike = [];
    public bool  $null = false;
    public bool  $notNull = false;

    protected AbstractType $type;

    /**
     * CategorizedValues constructor.
     *
     * @param  SearchParser  $searchParser
     *
     * @throws Exceptions\JsonQueryBuilderException
     */
    public function __construct(SearchParser $searchParser)
    {
        $this->searchParser = $searchParser;

        $this->type = (new TypesConfig())->getTypeClassFromTypeName($this->searchParser->type);

        $this->categorize();
        $this->format();
    }

    public function categorize()
    {
        foreach ($this->searchParser->values as $value) {
            if ($value === self::IS_NULL) {
                $this->null = true;
                continue;
            }

            if ($value === self::IS_NOT_NULL) {
                $this->notNull = true;
                continue;
            }

            if ($this->isNegated($value)) {
                $value = $this->replaceNegation($value);

                if ($this->hasWildCard($value) || $this->isSingleStringValue()) {
                    $value = $this->replaceWildCard($value);
                    $this->notLike[] = $value;
                    continue;
                }

                $this->not[] = $value;
                continue;
            }

            if ($this->hasWildCard($value) || $this->isSingleStringValue()) {
                $value = $this->replaceWildCard($value);
                $this->andLike[] = $value;
                continue;
            }

            $this->and[] = $value;
        }
    }

    /**
     * Format categorized values. It must be done after categorizing
     * because of micro operators.
     */
    public function format()
    {
        $this->and = $this->type->prepare($this->and);
        $this->andLike = $this->type->prepare($this->andLike);
        $this->not = $this->type->prepare($this->not);
        $this->notLike = $this->type->prepare($this->notLike);
    }

    protected function isNegated($splitValue): bool
    {
        return substr($splitValue, 0, 1) === self::NOT;
    }

    protected function hasWildCard(string $value): bool
    {
        if (!$value) {
            return false;
        }

        return $value[0] === self::LIKE || $value[strlen($value) - 1] === self::LIKE;
    }

    protected function replaceWildCard($value)
    {
        return str_replace(self::LIKE, '%', $value);
    }

    protected function replaceNegation($value)
    {
        return preg_replace('~' . self::NOT . '~', '', $value, 1);
    }

    // Hack so that LIKE operator is used when a single value of string type is passed.
    // Not happy with this solution, might need to refactor this later
    protected function isSingleStringValue(): bool
    {
        return count($this->searchParser->values) == 1 && $this->searchParser->type == 'string';
    }
}

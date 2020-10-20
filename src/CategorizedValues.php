<?php

namespace Voice\JsonQueryBuilder;

use Voice\JsonQueryBuilder\Config\OperatorsConfig;
use Voice\JsonQueryBuilder\Config\TypesConfig;
use Voice\JsonQueryBuilder\RequestParameters\Models\Search;

class CategorizedValues
{
    /**
     * Constants for micro-operator declaration.
     */
    const NOT = '!';
    const LIKE = '%';
    const IS_NULL = 'null';
    const IS_NOT_NULL = '!null';

    protected OperatorsConfig $operatorsConfig;
    protected Search          $searchModel;

    public array $and = [];
    public array $andLike = [];
    public array $not = [];
    public array $notLike = [];
    public bool  $null = false;
    public bool  $notNull = false;

    /**
     * CategorizedValues constructor.
     * @param OperatorsConfig $operatorsConfig
     * @param Search $searchModel
     * @throws Exceptions\JsonQueryBuilderException
     */
    public function __construct(OperatorsConfig $operatorsConfig, Search $searchModel)
    {
        $this->operatorsConfig = $operatorsConfig;
        $this->searchModel = $searchModel;

        $this->prepare();
        $this->categorize();
    }

    /**
     * @throws Exceptions\JsonQueryBuilderException
     */
    public function prepare()
    {
        $type = (new TypesConfig())->getCallbackByTypeName($this->searchModel->type);
        $this->searchModel->values = $type->prepare($this->searchModel->values);
    }

    public function categorize()
    {
        foreach ($this->searchModel->values as $value) {
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

                if ($this->hasWildCard($value)) {
                    $value = $this->replaceWildCard($value);
                    $this->notLike[] = $value;
                    continue;
                }

                $this->not[] = $value;
                continue;
            }

            if ($this->hasWildCard($value)) {
                $value = $this->replaceWildCard($value);
                $this->andLike[] = $value;
                continue;
            }

            $this->and[] = $value;
        }
    }

    protected function isNegated(string $splitValue): bool
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
}

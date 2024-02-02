<?php

declare(strict_types=1);

namespace Asseco\JsonQueryBuilder;

use Asseco\JsonQueryBuilder\Config\ModelConfig;
use Asseco\JsonQueryBuilder\Config\OperatorsConfig;
use Asseco\JsonQueryBuilder\Exceptions\JsonQueryBuilderException;
use Asseco\JsonQueryBuilder\Traits\CleansValues;
use Illuminate\Support\Facades\Config;

class CustomFieldSearchParser implements SearchParserInterface
{
    use CleansValues;

    /**
     * Constant by which values will be split within a single parameter. E.g. parameter=value1;value2.
     */
    const VALUE_SEPARATOR = ';';

    public string $column;
    private string $argument;
    public array  $values;
    public string $type;
    public string $operator;

    public string $cf_field_identificator = 'custom_field_id';
    public string $cf_field_value = '';

    private ModelConfig $modelConfig;

    /**
     * @param ModelConfig $modelConfig
     * @param OperatorsConfig $operatorsConfig
     * @param array $arguments
     * @throws JsonQueryBuilderException
     */
    public function __construct(ModelConfig $modelConfig, OperatorsConfig $operatorsConfig, array $arguments)
    {
        $this->modelConfig = $modelConfig;

        foreach($arguments as $col => $val) {
            if (str_contains($col, $this->cf_field_identificator)) {
                $this->cf_field_value = $val;
            }
            else {
                $this->column = $col;
                $this->argument = $val;
            }
        }

        $this->checkForForbiddenColumns();

        $this->operator = $this->parseOperator($operatorsConfig->getOperators(), $this->argument);
        $arguments = str_replace($this->operator, '', $this->argument);
        $this->values = $this->splitValues($arguments);
        $this->type = $this->getColumnType();
    }

    /**
     * @param  $operators
     * @param  string  $argument
     * @return string
     *
     * @throws JsonQueryBuilderException
     */
    protected function parseOperator($operators, string $argument): string
    {
        foreach ($operators as $operator) {
            $argumentHasOperator = strpos($argument, $operator) !== false;

            if (!$argumentHasOperator) {
                continue;
            }

            return $operator;
        }

        throw new JsonQueryBuilderException("No valid callback registered for $argument. Are you missing an operator?");
    }

    /**
     * Split values by a given separator.
     *
     * Input: val1;val2
     *
     * Output: val1
     *         val2
     *
     * @param  string  $values
     * @return array
     *
     * @throws JsonQueryBuilderException
     */
    protected function splitValues(string $values): array
    {
        $valueArray = explode(self::VALUE_SEPARATOR, $values);
        $cleanedUpValues = $this->cleanValues($valueArray);

        if (count($cleanedUpValues) < 1) {
            throw new JsonQueryBuilderException("Column '$this->column' is missing a value.");
        }

        return $cleanedUpValues;
    }

    /**
     * @return string
     *
     * @throws JsonQueryBuilderException
     */
    protected function getColumnType(): string
    {
        $columns = $this->modelConfig->getModelColumns();

        if (!array_key_exists($this->column, $columns)) {
            // TODO: integrate recursive column check for related models?
            return 'generic';
        }

        return $columns[$this->column];
    }

    /**
     * Check if global forbidden key is used.
     *
     * @throws JsonQueryBuilderException
     */
    protected function checkForForbiddenColumns()
    {
        $forbiddenKeys = Config::get('asseco-json-query-builder.global_forbidden_columns');
        $forbiddenKeys = $this->modelConfig->getForbidden($forbiddenKeys);

        if (in_array($this->column, $forbiddenKeys)) {
            throw new JsonQueryBuilderException("Searching by '$this->column' field is forbidden. Check the configuration if this is not a desirable behavior.");
        }
    }
}

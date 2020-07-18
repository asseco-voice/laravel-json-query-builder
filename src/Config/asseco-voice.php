<?php

use Voice\SearchQueryBuilder\RequestParameters\CountParameter;
use Voice\SearchQueryBuilder\RequestParameters\LimitParameter;
use Voice\SearchQueryBuilder\RequestParameters\OffsetParameter;
use Voice\SearchQueryBuilder\RequestParameters\OrderByParameter;
use Voice\SearchQueryBuilder\RequestParameters\RelationsParameter;
use Voice\SearchQueryBuilder\RequestParameters\ReturnsParameter;
use Voice\SearchQueryBuilder\RequestParameters\SearchParameter;
use Voice\SearchQueryBuilder\SearchCallbacks\Between;
use Voice\SearchQueryBuilder\SearchCallbacks\Equals;
use Voice\SearchQueryBuilder\SearchCallbacks\GreaterThan;
use Voice\SearchQueryBuilder\SearchCallbacks\GreaterThanOrEqual;
use Voice\SearchQueryBuilder\SearchCallbacks\LessThan;
use Voice\SearchQueryBuilder\SearchCallbacks\LessThanOrEqual;
use Voice\SearchQueryBuilder\SearchCallbacks\NotBetween;
use Voice\SearchQueryBuilder\SearchCallbacks\NotEquals;
use Voice\SearchQueryBuilder\Types\BooleanType;
use Voice\SearchQueryBuilder\Types\GenericType;

return [
    'search' => [

        /**
         * Registered request parameters.
         */
        'requestParameters'      => [
            SearchParameter::class,
            ReturnsParameter::class,
            OrderByParameter::class,
            RelationsParameter::class,
            LimitParameter::class,
            OffsetParameter::class,
            CountParameter::class,
        ],

        /**
         * Registered operators/callbacks. Operator order matters!
         * Callbacks having more const OPERATOR characters must come before those with less.
         */
        'operators'              => [
            NotBetween::class,
            LessThanOrEqual::class,
            GreaterThanOrEqual::class,
            Between::class,
            NotEquals::class,
            Equals::class,
            LessThan::class,
            GreaterThan::class,
        ],

        /**
         * Registered types. Generic type is the default one and should be used if
         * no special care for type value is needed.
         */
        'types'                  => [
            GenericType::class,
            BooleanType::class,
        ],

        /**
         * List of globally forbidden columns to search on.
         * Searching by forbidden columns will throw an exception
         * This takes precedence before other exclusions.
         */
        'globalForbiddenColumns' => [
            // 'id', 'created_at' ...
        ],

        /**
         * Refined options for a single model.
         * Use if you want to enforce rules on a specific model without affecting globally all models
         */
        'modelOptions'           => [

            /**
             * For real usage, use real models without quotes. This is only meant to show the available options.
             */
            'SomeModel::class' => [
                /**
                 * If enabled, this will read from model guarded/fillable properties
                 * and decide whether it is allowed to search by these parameters.
                 * If guarded property is present, fillable won't be taken. Laravel standard
                 * is to use one or the other, not both.
                 * This takes precedence before forbidden columns, but if both are used, it
                 * will behave like union of columns to be excluded.
                 * Searching on forbidden columns will throw an exception.
                 */
                'eloquentExclusion' => false,
                /**
                 * Disable search on specific columns. Searching on forbidden columns will throw an exception
                 */
                'forbiddenColumns'  => ['column', 'column2'],
                /**
                 * Array of columns to order by in 'column => direction' format.
                 * 'order-by' from query string takes precedence before these values.
                 */
                'orderBy'           => [
                    'id'         => 'asc',
                    'created_at' => 'desc'
                ],
                /**
                 * List of columns to return. Return values forwarded within the request will
                 * override these values. This acts as a 'SELECT /return only columns/' from.
                 * By default, 'SELECT *' will be ran.
                 */
                'returns'           => ['column', 'column2'],
                /**
                 * List of relations to load by default. These will be overridden if provided within query string.
                 */
                'relations'         => ['rel1', 'rel2'],

                /**
                 * TBD
                 * Some column names may be different on frontend than on backend.
                 * It is possible to map such columns so that the true ORM
                 * property stays hidden.
                 */
                'columnMapping'     => [
                    'frontendColumn' => 'backendColumn',
                ],
            ],
        ],

    ]
];

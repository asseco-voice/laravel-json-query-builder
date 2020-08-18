# Laravel JSON query builder

This package enables building queries from JSON objects following
the special logic explained below. 

## Installation

Install the package through composer. It is automatically registered
as a Laravel service provider.

``composer require asseco-voice/laravel-json-query-builder``

## Usage

In order to use the package, you need to instantiate ``JsonQuery()`` providing 
two dependencies to it. One is ``Illuminate\Database\Eloquent\Builder`` instance,
and the other is a JSON/array input.

Once instantiated, you need to run the ``search()`` method, and query will be
constructed on the provided builder object.

```
$jsonQuery = new JsonQuery($builder, $input);
$jsonQuery->search();
```

## Dev naming conventions for this package

- **parameter** is a top-level JSON key name (see the options [below](#parameter-breakdown))
- **arguments** are parameter values. Everything within a top-level JSON.
- **argument** is a single key-value pair.
- single argument is further broken down to **column / operator / value** 

```
{
    "search": {                         <-- parameter
        "first_name": "=foo",           <-- argument
        "last_name": "  =       bar  "  <-- argument   
         ˆˆˆˆˆˆˆˆˆ      ˆ       ˆˆˆ
          column    operator   value
    }
}
```

## Parameter breakdown
Parameters follow a special logic to query the DB. It is possible to use the following
JSON parameters (keys):

- ``search`` - will perform the querying logic (explained in detail [below](#search))
- ``returns`` - will return only the columns provided as values.
- ``order-by`` - will order the results based on values provided.
- ``relations`` - will load the relations for the given model.
- `limit` - will limit the results returned.
- `offset` - will return a subset of results starting from a point given. This parameter **MUST**
be used together with ``limit`` parameter. 
- `count` - will return record count.

### Search

The logic is done in a ``"column": "operator values"`` fashion in which we assume the 
following:

- `column` represents a column in the database. Multiple keys can be separated as a new
JSON key-value pair. 
- It is possible to search by related models using ``.`` as a divider i.e. 
`"relation.column": "operator value")`.
- ``operator`` is one of the available main operators for querying (listed [below](#main-operators))
- ``values`` is a semicolon (`;`) separated list of values 
(i.e. `"column": "=value;value2;value3"`) which
can have micro-operators on them as well (i.e. `"column": "=value;!value2;%value3%"`). 

#### Main operators

- `=` - equals
- `!=` - does not equal
- `<` - less than (requires exactly one value)
- `>` - greater than (requires exactly one value)
- `<=` - less than or equal (requires exactly one value)
- `>=` - greater than or equal (requires exactly one value)
- `<>` - between (requires exactly two values)
- `!<>` - not between (requires exactly two values)

Example:

```
{
    "search": {
        "first_name": "=foo",
        "last_name": "!=bar" 
    }
}
```

Will perform a ``SELECT * FROM some_table WHERE first_name IN 
('foo') AND last_name NOT IN ('bar')``.

#### Micro operators

- `!` - negates the value. Works only on the beginning of the value (i.e. `!value`).
- `%` - performs a `LIKE` query. Works only on a beginning, end or both ends of the 
value (i.e. `%value`, `value%` or `%value%`).
- logical operators are used to use **multiple operators** (you can't do `=1||2`, but `=1||=2`) for a 
single column (order matters!):
   - `&&` enables you to connect values using AND
   - `||` enables you to connect values using OR


```
{
    "search": {
        "first_name": "=!foo",
        "last_name": "=bar%" 
    }
}
```

Will perform a ``SELECT * FROM some_table WHERE first_name NOT IN 
('foo') AND last_name LIKE 'bar%'``.

Notice that here ``!value`` behaved the same as ``!=`` main operator. The difference
is that ``!=`` main operator negates the complete list of values, whereas the 
``!value`` only negates that specific value. I.e. `!=value1;value2` is semantically
the same as ``=!value1;!value2``.

Logical operator example: 

```
{
    "search": {
        "first_name": "=foo||=bar",
    }
}
```

Will perform ``SELECT * FROM some_table WHERE first_name IN 
 ('foo') OR first_name IN ('bar')``.

Note that logical operators are using standard bool logic precedence,
therefore ``x AND y OR z AND q`` is the same as `(x AND y) OR (z AND q)`.

### Returns

Using a ``returns`` key will effectively only return the fields given within it.
This operator accepts an array of values or a single value. 

Example:

Return single value:
```
{
    "returns": "first_name",
}
```
Will perform a ``SELECT first_name FROM ...``

Return multiple values:
```
{
    "returns": ["first_name", "last_name"]
}
```
Will perform a ``SELECT first_name, last_name FROM ...``

### Order by

Using ``order-by`` key does an 'order by' based on the given key(s). Order of the keys
matters!

Arguments are presumed to be in a ``"column": "direction"`` fashion, where `direction`
MUST be ``asc`` (ascending) or `desc` (descending); everything else will throw an
exception.

Example:
```
{
    "order-by": {
        "first_name": "asc",
        "last_name": "desc" 
    }
}
```

Will perform a ``SELECT ... ORDER BY first_name asc, last_name desc``

### Relations

It is possible to load object relations as well by using ``relations`` parameter.
This operator accepts an array of values or a single value. 

Example:

Resolve single relation:
```
{
    "relations": "containers",
}
```

Resolve multiple relations:
```
{
    "relations": ["containers", "addresses"]
}
```

Relations, if defined properly and following Laravel convention, should be predictable
to assume:

- 1:M & M:M - relation name is in plural (i.e. Contact has many **Addresses**, relation 
name is thus 'addresses')
- M:1 - relation name is in singular (i.e. Comment belongs to a **Post**, relation
name is thus 'post')
- **important**: since Laravel returns API responses as **snake_case**, it is enabled to
provide a **snake_case'd** relation (even though **camelCase** works as well) for multi-word
relations. I.e. doing ``"relations": "workspace_items"`` is the equivalent of calling 
``"relations": "workspaceItems"``, but it is recommended to use **snake_case** approach.

It is possible to recursively load relations using dot notation:

```
{
    "relations": "media.type"
}
```

This will load media relations as well as resolve media types right away. If you have the
need to resolve multiple second level relations you can provide an array of those:

```
{
    "relations": ["media.type", "media.category"]
}
```

This will load media relations together with resolved type and category for each media object. 

It is also possible to stack relations using dot notation without a limit. It must be taken 
into account though that this can **seriously hurt performance**!

```
{
    "relations": "media.type.contact.title"
}
```

### Limit

You can limit the number of results fetched by doing:

```
{
    "limit": 10
}
```

This will do a ``SELECT * FROM table LIMIT 10``.

### Offset

You can use offset to further limit the returned results, however it
requires using limit alongside it. 

```
{
    "limit": 10,
    "offset": 5
}
```

This will do a ``SELECT * FROM table LIMIT 10 OFFSET 5``.

### Count

You can fetch count of records instead of concrete records by adding the count key:

```
{
    "count": true
}
```

This will do a ``SELECT count(*) FROM table``.

## Top level logical operators

Additionally, it is possible to group search clauses by top-level logical operator.

Available operators:
- ``&&`` AND
- ``||`` OR

**Using no top-level operator will assume AND operator.**

### Examples 

These operators take in a single object, or an array of objects, with few differences worth mentioning.
Single object will apply the operator on given attributes:

```
{
    "search": {
        "&&": {
            "id": "=1",
            "name": "=foo"
        }
    }
}
```

Resulting in ```id=1 AND name=foo```. 
Whereas an array of objects will apply the operator between array objects, **not** within the objects themselves:

```
{
    "search": {
        "||": [
            {
                "id": "=1",
                "name": "=foo"
            },
            {
                "id": "=2",
                "name": "=bar"
            }
        ]
    }
}
```

Resulting in ``(id=1 AND name=foo) OR (id=2 AND name=bar)``. This is done intentionally, default operator is
AND, thus it will be applied within objects.

If you'd like inner attributes changed to OR instead, you can go recursive:

```
{
    "search": {
        "||": [
            {
                "||": {
                    "id": "=1",
                    "name": "=foo"
                }
            },
            {
                "id": "=2",
                "name": "=bar"
            }
        ]
    }
}
```

Resulting in ``(id=1 OR name=foo) OR (id=2 AND name=bar)``.

### Absurd examples

Since logic is made recursive, you can go as absurd and deep as you'd like, but at this point
it may be smarter to revise what do you actually want from your life and universe:

```
{
    "search": {
        "||": {
            "&&": [
                {
                    "||": [
                        {
                            "id": "=2||=3",
                            "name": "=foo"
                        },
                        {
                            "id": "=1",
                            "name": "=foo%&&=%bar"
                        }
                    ]
                },
                {
                    "we": "=cool"
                }
            ],
            "love": "<3",
            "recursion": "=rrr"
        }
    }
}
```

Breakdown:

- Step 1
```
{
    "id": "=2||=3",
    "name": "=foo"
},
```
Result: ``(id=2 OR id=3) AND name=foo``

- Step 2
```
{
    "id": "=1",
    "name": "=foo%&&=%bar"
}
```
Result: ``id=1 AND (name LIKE foo% AND name LIKE %bar)``

- Step 3 (merge)

```
"||": [
    {...},
    {...}
]
```
Result: ``(step1) OR (step2)``

- Step 4 ``we=cool``
```
{
    "we": "=cool"
}
```

- Step 5 (merge) 
```
"&&": [
    {
        "||": [...]
    },
    {
        "we": "=cool"
    }
],
```
Result: ``(step3) AND (step4)``

- Step 6 (ultimate merge)

```
"||": {
    "&&": [...],
    "love": "<3",
    "recursion": "=rrr"
}
```
Result: ``(step5) OR love<3 OR recursion=rrr``

The final query (kill it with fire):

``((((id=2 OR id=3) AND name=foo) OR (id=1 AND (name LIKE foo% AND name LIKE %bar))) AND we=cool) OR love<3 OR recursion=rrr``

## Config 

Aside from standard query string search, it is possible to provide additional 
package configuration.

Publish the configuration by running 
`php artisan vendor:publish --provider="Voice\JsonQueryBuilder\JsonQueryServiceProvider"`.

All the keys within the configuration file have a detailed explanation above each key.

### Package extensions

Once configuration is published you will see several keys which you can extend with your
custom code. 

- request parameters are registered under ``request_parameters`` config key. 
You can extend this functionality by adding your own custom parameter. It 
needs to extend ``Voice\JsonQueryBuilder\RequestParameters\AbstractParameter``
in order to work.  
- operators are registered under ``operators`` config key. Those can be 
extended by adding a class which extends ``Voice\JsonQueryBuilder\SearchCallbacks\AbstractCallback``
- types are registered under ``types`` config key. Those can be extended
by adding a class which extends ``Voice\JsonQueryBuilder\Types\AbstractType``

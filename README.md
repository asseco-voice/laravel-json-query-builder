# Laravel search query builder

This package enables ``search`` method on Eloquent models for 
Laravel 7 to enable detailed DB search through URL query string. 

It functions out-of-the-box automatically for all Eloquent models 
within the project. No additional setup is needed.

PHP min version: 7.4.

## Installation

Install the package through composer. It is automatically registered
as a Laravel service provider, so no additional actions are required.

``composer require asseco-voice/laravel-search-query-builder``

## Quick usage

Create a GET search endpoint

```
Route::get('search', 'ExampleController@search');
```

Call the method within the controller and forward a full `Illuminate\Http\Request` object to the search method.

```
public function search(Request $request)
{
    return SomeModel::search($request)->get();
}
```
 
Call the endpoint providing the query string:

```
www.example.com/search?search=(first_name=foo;bar;!baz\last_name=test)
```
    
This will perform a ``SELECT * FROM some_table WHERE first_name IN ('foo, 'bar') 
AND first_name not in ('baz') or last_name in ('test')``.

## Dev naming conventions for this package

- **parameter** is a query string key name (i.e. `?key=...`)
- **arguments** are query string values (i.e. `?key=( ... value ...)`),
or more precisely everything coming after ``=`` sign after query string key
    - **argument** is a single key-value pair within parameter values
(i.e. `?key=( key=value, key=value )`). 
        -  single argument is further broken down to **column / operator / value** 

## Parameter breakdown
Parameters follow a special logic to query the DB. It is possible to use the following
query string parameters (keys):

- ``search`` - will perform the querying logic (explained in detail below)
- ``returns`` - will return only the columns provided as values (underlying logic is that 
it actually does `SELECT /keys/ FROM` instead of `SELECT * FROM`)
- ``order-by`` - will order the results based on values provided
- ``relations`` - will load the relations for the given model.
- `limit` - will limit the results returned
- `offset` - will return a subset of results starting from a point given. This parameter MUST
be used together with ``limit`` parameter. 
- `count` - will return record count

Parameters can be chained in the same fashion the query strings are chained i.e. 
``?search=(...)&returns=(...)&odrer-by=(...)``.

### Search

The logic is done in a ``(column operator values)`` fashion in which we assume the 
following:

- ``( ... )`` - everything needs to be enclosed within parenthesis
- `column` represents a column in the database. Multiple keys can be separated with a 
backslash ``\`` i.e. `(column=value\column2=value2)`. It is possible to search by related
models using ``.`` as a divider i.e. `(relation.column=value)`.
- ``operator`` is one of the available main operators for querying (listed below)
- ``values`` is a semicolon (`;`) separated list of values 
(i.e. `(column=value;value2;value3)`) which
can have micro-operators on them as well (i.e. `column=value;!value2;*value3*`). 

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
?search=(first_name=foo\last_name!=bar)
```

Will perform a ``SELECT * FROM some_table WHERE first_name IN 
('foo') AND last_name NOT IN ('bar')``.

#### Micro operators

- `!` - negates the value. Works only on the beginning of the value (i.e. `!value`).
- `*` - performs a `LIKE` query. Works only on a beginning, end or both ends of the 
value (i.e. `*value`, `value*` or `*value*`). `*` gets converted to `%`. `%` can't be
used because it is a reserved character in query strings.

```
?search=(first_name=!foo\last_name=bar*)
```

Will perform a ``SELECT * FROM some_table WHERE first_name NOT IN 
('foo') AND last_name LIKE 'bar%'``.

Notice that here ``!value`` behaved the same as ``!=`` main operator. The difference
is that ``!=`` main operator negates the complete list of values, whereas the 
``!value`` only negates that specific value. I.e. `key!=value1;value2` is semantically
the same as ``key=!value1;!value2``.

### Returns

Using a ``returns`` key will effectively only return the fields given within it.
Everything needs to be enclosed within parenthesis ``( ... )``, and separating
values is done in the same fashion as with values within a ``search`` parameter; 
with a backslash ``\``.

Example:

```
?returns=(first_name\last_name)
```

Will perform a ``SELECT first_name, last_name FROM ...``

### Order by

Using ``order-by`` key does an 'order by' based on the given key(s). If no value
is provided to a key, it is assumed that order is ascending. Order of the keys
matters!

Example:

```
?order-by=(first_name\last_name=desc)
```

Will perform a ``SELECT ... ORDER BY first_name asc, last_name desc``

Explicitly saying ``first_name=asc`` would do the same, however using anything
besides ``asc/desc`` as a value will throw an exception. 

### Relations

It is possible to load object relations as well by using ``relations`` parameter.
Same convention is followed:

```
?relations=(...\...)
```

Relations, if defined properly and following Laravel convention, should be predictable
to assume:

- 1:M & M:M - relation name is in plural (i.e. Contact has many **Addresses**, relation 
name is thus 'addresses')
- M:1 - relation name is in singular (i.e. Comment belongs to a **Post**, relation
name is thus 'post')
- **important** loading relations with more than 1 word should be fetched using **camelCase** which 
will in turn get the relation back as a snake_case equivalent in the JSON response 

It is possible to recursively load relations using dot notation. 

I.e. ``?relations=(contact)`` will load contact relations, using `?relations=(contact.title)`
will load contact and load titles within contacts. It is also possible to load
multiple second level relations by using for example 
``?relations=(contact.title\contact.media)`` which will load contact as a main relation,
and title and media as a contact relation.

### Limit

You can limit the number of results fetched by doing:

```
?limit=10
```

This will do a ``SELECT * FROM table LIMIT 10``.

### Offset

You can use offset to further limit the returned results, however it
requires using limit alongside it. 

```
?limit=10&offset=5
```

This will do a ``SELECT * FROM table LIMIT 10 OFFSET 5``.

### Count

You can fetch count of records instead of concrete records by adding the count key:

```
?count
```

This will do a ``SELECT count(*) FROM table``.

## Config 

Aside from standard query string search, it is possible to provide additional 
package configuration.

Publish the configuration by running 
`php artisan vendor:publish --provider="Voice\SearchQueryBuilder\SearchServiceProvider"`.

All the keys within the configuration file have a detailed explanation above each key.

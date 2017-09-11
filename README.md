#Trinity Search


[![Coverage Status](https://coveralls.io/repos/github/modpreneur/trinity-search/badge.svg?branch=master)](https://coveralls.io/github/modpreneur/trinity-widgets?branch=master)
[![Build Status](https://travis-ci.org/modpreneur/trinity-search.svg?branch=master)](https://travis-ci.org/modpreneur/trinity-settings)


Trinity search is part of Trinity package.

Description:

* Finds array of objects by given query

Base route:

```sh
/admin/search/{entity}/?q=
```

##Syntax
Append query to the base route. Query can be composed of:

* (optional) Column selection - put columns which you want to return into simple brackets. If you want to access column from associated table, simply put colon and name of the column from associated table
```sh
(column1,column2,column3,column4:attributeFromAssociatedTable:anotherAttribute)
```
* (optional) Conditions - put conditions into curly brackets. Available operators: <, >, =, <=, >=, !=, AND, OR
```sh
{column1 > 500 AND column2 < 800 OR (column3 = <str>JohnDoe</str> AND column4 <= 20)}
```
* String Values must be wrapped inside <str></str> block
```sh
{name = <str>Jack</str> AND description LIKE <str>%it started as "game"%</str>}
```
* (optional) Limit - for limit 5 rows simply append
```sh
LIMIT=5
```
* (optional) Offset - for offset 10 rows simply append
```sh
OFFSET=10
```
* (optional) Ordering - for ordering result, append keyword ORDER BY and then columns and directions by which you want to order, multiple columns ordering are supported - separate columns by comma
```sh
ORDERBY column1 ASC, column2 DESC
```

<br />

#####Example
```sh
/admin/search/product/?q=
(id,name,defaultBillingPlan:initialPrice)
{defaultBillingPlan:initialPrice > "14"} LIMIT=10 OFFSET=0 ORDERBY clients:name ASC, defaultBillingPlan:initialPrice DESC
```

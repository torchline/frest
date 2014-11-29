Introduction
=========

This framework automatically fits a facade layer around your SQL tables and provides very configurable and powerful API endpoints. Just specify the desired behavior in a json file for each resource and you're ready to go.

Define what is possible to be read back, what is read back by default, how it will look, etc.


Features
=========

- URL routing for resources
- CRUD operations
- unlimited related resource nesting in a single request
- functions on resources for custom behavior
- type checking
- pagination
- conditional searching (>, <, LIKE, etc.)


Installation
=========
1. Composer - "torchline/frest"
2. 

Querying
=========
FUNCTIONS:
	Names:
		gt, gte, lt, lte, in, like
	Usage:
		?username=like(FullMetal~) - starts with
		?money=gt(50) - greater than
		?hair=in(blonde,black,red) - is one of

PARTIAL READS:
	Usage:
		?fields=id,username,email - read only these fields back
		?fields=* - read all fields back
		(if no fields specified, it goes to default)

		?fields=name,owner(id, firstName, lastName) - do partial reads on child objects

MISCELLANEOUS:
	suppress_http_error_codes=true - make request always return 200 with actual http code embeded in response

# ChangeLog

Changes between versions

## Current changes

* remove service provider
* remove factory
* remove route collection provider
* route specific before/after dispatch hooks

## v0.5

### v0.5.0

* remove existing trailing slash from Request URI before attempting to find a matching
route
* *router.dispatcher.beforeDispatch* will not be executed without the Route object,
if segment-based URI matching is enabled, and the request matches a controller and
method
* added segment-based URI matching

## v0.4

### v0.4.1

* stop attempting to create a valid URL in response with the request host, let http
protocol handle it

### v0.4.0

* if 'route.dispatcher.routeNotFound' hook call returns a valid Route object the
request is dispatched to it
* add redirect helper method in response object
* enable multiple request HTTP methods per route definition
* route definition defaults to HTTP method GET if not specified
* set route as default that is matched by the empty URI request
* enable multiple request URIs matching same route through RegExp ORs
* create request object from pre set uri

## v0.3

### v0.3.0

* initial version

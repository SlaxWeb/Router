# ChangeLog

Changes between versions

## Current changes
* remove existing trailing slash from Request URI before attempting to find a matching
route

## v0.4

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

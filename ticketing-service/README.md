## Running the service

The first build may be slow since grpc and protobuf php extension have to be installed to run the containers. All
following runs will be fast.

```shell
ddev start
```

## Container settings

Additional packages are installed through `.ddev/web-build/*` dockerfiles.

We also have memcached service running through:

- `.ddev/docker-compose.memcached.yaml`
- `.ddev/docker-compose.eventstore.yaml`
- `.ddev/docker-compose.vitess.yaml`

## Queues

To run the queue for event sourcing, use the command:

```
ddev artisan queue:work --queue=laravel-event-sourcing
```

To run the queue to process ticket purchase events, use the command:

```
ddev artisan queue:work --queue=commands
```

## Vitess (for _MySQL_ option)

First you need to setup your local environment to run Vitess:

https://vitess.io/docs/22.0/get-started/local-mac/

Setting up a cluster using local scripts did not work properly, so Docker setup was used to run the Vitess service (run
with ddev on start).

Vitess test server does not account for persisting options when running through docker, so run the
`./.ddev/vitess/init.sql` on vitess every time the container is started.

To sync `VSchema` with the container run:

```shell
vtctldclient --server=localhost:33575 ApplyVSchema --vschema-file=./.ddev/vitess/vschema.json KEYSPACE_NAME
```

_(`KEYSPACE_NAME` should be `default` by default. It is defined in the `.ddev/docker-compose.vitess.yaml` file)_

## EventStoreDB

To visit the dashboard go to http://localhost:2113/web/index.html#/dashboard

In `.ddev/docker-compose.eventstore.yaml` it is possible to pick clustered vs non-clustered setup. Restart ddev
after choosing one.

## Caching discovered projectors and reactors

In production, you likely do not want the package to scan all of your classes on every request. Therefore, during your
deployment process, you should run the command:

```
php artisan event-sourcing:cache-event-handlers
```

This will cache a manifest of all of your application's projectors and reactors. This manifest will be used by the
package to speed up the registration process.

The command:

```
php artisan event-sourcing:clear-event-handlers
```

may be used to remove the manifest.

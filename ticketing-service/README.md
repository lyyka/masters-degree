## Running the service

The first build may be slow since grpc and protobuf php extension have to be installed to run the containers. All
following runs will be fast.

```shell
ddev start
```

## Container settings

Additional packages are installed through `.ddev/web-build/*` dockerfiles.

We also have memcached service running through
`.ddev/docker-compose.memcached.yaml`

## Queues

To run the queue for event sourcing, use the command:

```
ddev artisan queue:work --queue=laravel-event-sourcing
```

To run the queue to process ticket purchase events, use the command:

```
ddev artisan queue:work --queue=commands
```

## EventStoreDB

To visit the dashboard go to http://localhost:2113/web/index.html#/dashboard

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

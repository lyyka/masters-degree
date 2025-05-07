## Running queue

To run the queue for event sourcing, use the command:

```
ddev artisan queue:work --queue=laravel-event-sourcing
```

On the reporting service, run this command to listen for messages:

```
ddev artisan queue:work --queue=ticket-events-outgoing
```

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

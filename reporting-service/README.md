# API

Use the API to gather relevant metrics:

- /api/metric/total_reservations -> Total number of reservations (each reservation can have X tickets on it)
- /api/metric/total_tickets -> Total number of tickets
- /api/metric/total_reservations_value -> Total price of all reservations
- /api/metric/total_checked_in -> Total checked in reservations
- /api/metric/total_cancelled -> Total cancelled reservations
- /api/metric/total_ticket_holders_updated -> Total number of reservations where ticket holder is updated at least once
- /api/metric/total_ticket_holders_updated_times -> Total number of ticket holder updates overall

This may not be the best read model and can be significantly improved with keeping aggregate data in separate table, such as total number of tickets, total price, etc., BUT this is built just to showcase one way to do it. It can also be extended to hold more data and make itself more relevant.

## Queue

Run this command to listen for messages coming in from ticketing service:

```
ddev artisan queue:work --queue=ticket-events-outgoing
```

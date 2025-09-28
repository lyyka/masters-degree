# create stored events in the sharded keyspace
create table `default`.stored_events
(
    id                bigint unsigned auto_increment
        primary key,
    aggregate_uuid    char(36)                     not null,
    aggregate_version bigint unsigned              null,
    event_version     tinyint unsigned default '1' not null,
    event_class       varchar(255)                 not null,
    event_properties  json                         not null,
    meta_data         json                         not null,
    created_at        timestamp                    not null,
    constraint stored_events_aggregate_uuid_aggregate_version_unique
        unique (aggregate_uuid, aggregate_version)
)
    collate = utf8mb4_unicode_ci;

create index stored_events_aggregate_uuid_index
    on `default`.stored_events (aggregate_uuid);

create index stored_events_event_class_index
    on `default`.stored_events (event_class);

create table `default`.snapshots
(
    id                bigint unsigned auto_increment
        primary key,
    aggregate_uuid    char(36)        not null,
    aggregate_version bigint unsigned not null,
    state             json            not null,
    created_at        timestamp       null,
    updated_at        timestamp       null
)
    collate = utf8mb4_unicode_ci;

create index snapshots_aggregate_uuid_index
    on `default`.snapshots (aggregate_uuid);

# create sequence in unsharded namespace
create table unsh.stored_events_seq
(
    id      bigint,
    next_id bigint,
    cache   bigint,
    primary key (id)
) comment 'vitess_sequence';
insert into unsh.stored_events_seq(id, next_id, cache)
values (0, 1, 100);

create table unsh.snapshots_seq
(
    id      bigint,
    next_id bigint,
    cache   bigint,
    primary key (id)
) comment 'vitess_sequence';
insert into unsh.snapshots_seq(id, next_id, cache)
values (0, 1, 100);

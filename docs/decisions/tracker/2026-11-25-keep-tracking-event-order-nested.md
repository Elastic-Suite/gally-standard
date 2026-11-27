---
status: accepted
date: 2026-11-25
ticket: "#1362838"
decision-makers: Pierre Gauthier
---

# Keep `order` nested in the `tracking_event` mapping

## Context and Problem Statement

In `tracking_event`, every dotted source field is mapped as `nested`: `SourceField::isNested()`
is derived from the code (`str_contains($code, '.')`). So `order`, `cart`, `display`, `context`
and the other event groups are nested, although each event holds a single object.

The `behavioral_data` transform cannot use native aggregations on nested fields. `revenue` is
computed with a `scripted_metric` that reads `order.row_total` from `_source`. Switching these
groups to `object` looked like a way to simplify and speed up the transform.

Do we switch the event groups to `object` as part of the behavioral data work?

## Decision Drivers

* The transform must stay cheap as the event volume grows.
* A mapping change on a data stream must not lose or corrupt tracked history.

## Considered Options

* Keep the current mapping
* Switch the event groups to `object`

## Decision Outcome

Chosen option: "Keep the current mapping", because the gain is limited to one aggregation and
the migration needs mechanisms Gally does not have yet.

### Consequences

* Good, because existing projects keep their tracked data and templates unchanged.
* Bad, because `revenue` stays a `scripted_metric`.

## Pros and Cons of the Options

### Switch the event groups to `object`

* Good, because `revenue` becomes a native `sum`.
* Neutral, because `views` and `orders` stay `scripted_metric` anyway: OpenSearch transforms have
  no `filter` aggregation, so counting events by type needs a script whatever the mapping.
* Neutral, because the transform is incremental and asynchronous, and the revenue script only
  reads `_source` for `order` events. Its cost does not reach product indexing.
* Bad, because `object` source fields are not implemented: `ObjectSourceFieldConverter::getFields()`
  returns `[]`, and `isNested()` would have to stop relying on the dot in the code.
* Bad, because index templates are only refreshed by the Doctrine listener on `SourceField`.
  SQL migrations never refresh them, so a migration alone would not change the mapping.
* Bad, because three cases have no solution yet:
  * **Data stream:** an incompatible change only applies to new backing indices. Converting the
    history means a rollover, a reindex of each old backing index, and its deletion. A reindex
    resets the ISM age, so converted history lives one more retention period.
  * **Transform source:** transforms read the write backing index and must match its mapping.
    They must be stopped during a history conversion, and nothing tells which transforms read
    which source.
  * **Transform target:** nothing detects a mapping change on `behavioral_data` or
    `tracking_session`. The target keeps its old mapping until the next source rollover.

## More Information

Reconsider this decision when one of these happens: the transform becomes a bottleneck, a
consumer needs native aggregations on event fields, or the mapping migration mechanisms above
are built for another reason.

Mapping defects found on the way, to fix separately:

* Ten Tracker source fields are declared with type `'integer'` instead of `int` in raw SQL
  migrations, so they get no Gally mapping (dynamic `long`, and `text` for `order.order_id`).
* The rename of `context.context_type` and `context.context_code` is in the database but not in
  the index templates, for the reason given above.

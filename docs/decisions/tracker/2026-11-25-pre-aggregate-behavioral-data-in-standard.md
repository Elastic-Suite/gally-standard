---
status: accepted
date: 2026-11-25
ticket: "#1362838"
decision-makers: Pierre Gauthier
---

# Pre-aggregate behavioral data per product and day in gally-standard

## Context and Problem Statement

Premium boosts and sorts need behavioral statistics on each product document (`_stats`: views,
sales, conversion rate, revenue, with running totals and moving averages). They are computed
from the raw `tracking_event` data stream.

ElasticSuite computes them at product reindex time, with aggregation queries on the raw events.
The analysis (Redmine #1323626) chose instead a continuous transform that aggregates the events
per product and day into a `behavioral_data` index, read at product indexing time.

Which repository owns that transform and its index: premium, which is the only consumer, or
standard, which owns the tracking data?

## Decision Drivers

* Statistics must be available as soon as a project enables the feature, without a history
  rebuild.
* The raw events have a shorter retention than the history the statistics need (up to 365 days
  with weekly stats).

## Considered Options

* Continuous transform into a daily `behavioral_data` index, owned by premium
* Continuous transform into a daily `behavioral_data` index, owned by standard

## Decision Outcome

Chosen option: "Continuous transform into a daily `behavioral_data` index, owned by standard",
because it starts collecting history from the moment tracking runs, whether premium is installed
or not, so no backfill is needed.

The `Tracker` module provisions one `behavioral_data` index per localized catalog
(`BehavioralDataTransformProvisioner`), one document per product (sku) and day, holding `views`,
`orders` and `revenue`. Premium only reads it when it builds `_stats`.

Two choices follow from the transform, and differ from the original specification:

* **No data stream or ILM for `behavioral_data`.** A transform target must be a concrete index:
  the transform upserts documents by id. Like the session index, a new `behavioral_data` index is
  created at each `tracking_event` rollover, and the transform is recreated on the current write
  backing index (`BehavioralDataIndexRolloverManager`). The day of a rollover is split between two
  indices, which is harmless because readers sum over the alias.
* **Its own retention** (`gally.tracking_behavioral_data_settings.retention`, 365 days by
  default), independent from the `tracking_event` retention: the aggregated history can outlive
  the raw events.

### Consequences

* Good, because a project that enables premium later already has the history it tracked since
  this feature was released. No backfill is needed.
* Good, because `behavioral_data` can serve other consumers later (KPIs, CTR with `display` and
  `add_to_cart` events) without premium.
* Bad, because standard projects without premium run a transform whose output nobody reads.
* Bad, because statistics lag behind events by up to one transform period (5 minutes by
  default). Moving averages exclude the current day anyway.
* Neutral, because events tracked before this release are never aggregated.

## Pros and Cons of the Options

### Continuous transform, owned by premium

* Good, because standard projects do not run a transform they do not use.
* Bad, because history only starts when premium is installed and enabled. Statistics need up to
  a year of history, so a backfill from the raw events would be required, and it would be capped
  by their retention.

## More Information

A first backfill implementation (a one-shot transform from the old backing indices) was written
for the premium-owned option, then set aside when the index moved to standard. It may come back
if the `behavioral_data` mapping ever has to change and older indices need rebuilding.

A benchmark against ElasticSuite (Redmine #1362838, 2026-11-24) measured the indexing overhead
of `_stats`: +0.3% (30 days, no weekly stats) and +5.4% (weekly stats over 365 days) on Gally,
for 5M views and 100k orders.

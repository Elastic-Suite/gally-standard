# gally-standard - the open-source engine

This repo (`Elastic-Suite/gally-standard`) holds every core feature of Gally. It is usually
checked out inside the Gally monorepo at `api/packages/gally-standard`, as its own git repo:
commit here, not in the monorepo. The monorepo map is `../../../AGENTS.md`.

The monorepo also exposes this folder as `api/vendor/gally/gally-standard`, a symlink to the same
files. Open and edit it through `api/packages/`.

## Module layout

`src/<Module>/`, namespace `Gally\<Module>`, PSR-4 on `src/`. Each module follows the same
internal shape: `Entity/`, `Repository/`, `Service/`, `State/` (API Platform providers and
processors), `Resolver/` (GraphQL), `EventSubscriber/`, `Resources/config/` (services and API
Platform resource YAML), `Tests/`.

Grouped by what they do:

- **Search path** - `Search/` (the big one: `Elasticsearch/`, `Compiler/`, `Decoration/`,
  `Hydrator/`, `Resolver/`, `Service/`), `Analysis/` (analyzers), `RuleEngine/` (rule to ES query).
- **Data model and config** - `Metadata/` (source fields, attribute config: the heart of the
  configurable data model), `Catalog/`, `LocalizedCatalog` (inside `Catalog/`), `Locale/`,
  `Configuration/` (scoped settings), `Category/`, `Product/`, `Bundle/`.
- **Indexing** - `Index/` (index lifecycle, `Command/` for `gally:index:*`), `Job/` (async jobs and
  messenger handlers).
- **API plumbing** - `GraphQl/`, `Hydra/`, `OpenApi/`, `Stitching/` (merges the dynamic
  source-field schema into the API), `ResourceMetadata/`, `Routing/`, `RequestContext/`,
  `Serializer` bits, `Validator/`, `Doctrine/` (custom DQL, filters, types), `File/`, `Email/`,
  `Cache/`.
- **Users and security** - `User/` (entity, roles, fixtures, forgot-password), `Security/`.
- **Analytics** - `Tracker/` (tracking-event ingestion into the `tracking` messenger transport).
- **Dev support** - `Fixture/`, `Test/`, `Migrations/`, `Menu/` (drives the back-office nav from
  the API).

## Where a feature lives when it is not obvious

| Concern | Start here |
|---|---|
| Facet configuration: entity, repository, API providers | `src/Search/{Entity,Repository,State}/Facet/` - **not** under `Metadata/`, despite being per-attribute |
| One specific CSV import or export job | the **entity's** module, e.g. `Metadata/Job/` for source fields. `Job/Service/Csv/` holds only the generic base classes |
| Doctrine migrations | `src/Migrations/` (namespace `Gally\Migrations`), which also holds the `GallyComparator` |

## Extension

Premium and client code extend this engine by decorating its services (`Decoration/`) or
subscribing to its events, not by editing it. If a behaviour here seems overridden at runtime,
look for a decorator in `gally-premium`.

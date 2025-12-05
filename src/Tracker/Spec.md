
---

### Todo demain

- Bug, self reindex va creer des index pour mon entité, il faut exclure les time serie


### Questions

- Migration pour créer l'ISM ?
  - à la creation de l'index

- Metadata pour les events ?
  - pas exposé au public (voter)
  - simplifier la génération du mapping
  - quelles utilitées

---

h1. Todo

Check how to build tracked data structure according to the structure that existing in elasticsuite.
- Try to use timeseries
- Try to build session with sequence (spark ?)
- How we can improve tracking data to archive it ?
- Do we need to keep event index after one month ?
- Do we need a specific index for behavioral data (conv. rate as timeseries) ?

h1. Analyze

h2. Timeseries vs normal index

Time-series indices are specifically designed for timestamped data and offer significant performance improvements for tracking events. They provide better compression, faster ingestion, and optimized queries for time-based analytics.

|_. Aspect |_. Normal index |_. Time_series index |
| Filtering by timestamp | Standard, performant | *Highly optimized: segments are sorted, faster reads* |
| Filtering by keyword/numeric field | Standard, performant | Same performance, sequential read optimized |
| Aggregations on low-cardinality fields | Very efficient | Very efficient, same performance |
| Aggregations on high-cardinality fields | Limited by memory/bucket size | Same limitation, no magic |
| Storage / compression | Standard | *Optimized for large volumes of timestamped events* |
| Storage of "normal" fields (text, URL, labels) | Allowed, no limit | Allowed, no limit |
| Continuous event ingestion | Standard | *Optimized for continuous ingestion, fewer segment merges* |
| Maintenance / rollover |  Recommended to use index per period (month/week), easy to manage | Recommended to use index per period (month/week), easy to manage |
| Real-time dashboards | Yes, may need refresh | *Yes, very well suited for near real-time dashboards* |
| Limits / precautions | Aggregation cardinality, fragmented segments | Aggregation cardinality remains the main limitation, but segments are better organized |

h2. Ingestion Strategy

* PostgreSQL as buffer (similar to Magento process)
* Symfony Messenger for asynchronous processing
* Bulk insert to OpenSearch via message handlers
* Retry mechanism for failed insertions

h2. Data Retention Configuration

Use OpenSearch Index Lifecycle Management (ILM) and datastream for automated retention and rollover.

h2. Trackings data

{{collapse(View details...)
|_. Request type |_. Elasticsuite |_. Gally proposal |
| Common data | <pre>
{
"event_id": "12598f4980a4d55cb4af4791cc66e354",
"date": "2025-05-02 08:53:05",
"created_at": "2025-05-02 08:53:05",
"is_invalid": false,
"page": {
"store_id": 3,
"type": {
"identifier": "catalogsearch_result_index",
"label": "Quick Search Form"
},
"locale": "fr_FR",
"site": "demo.fr",
"url": "\/etep\/payment\/redirect\/",
"title": "Test",
"resolution": {
"x": 1920,
"y": 1032
},
"referrer": {
"domain": "demo.fr",
"page": "\/checkout\/cart\/"
}
},
"session": {
"uid": "2eaaf2d5-035f-777c-e24c-2ed081eed187",
"vid": "e0d32f3e-b328-0c37-4e62-72337ed51010"
},
"customer": {
"group_id": 36
},
"ab_campaigns": [
{
"id": 1,
"scenario": "A"
}
]
}
</pre> | <pre>
{
  "@timestamp": "2025-05-02T08:53:05Z",

"event_type": "XXX",
"metadata_code": "XXX",
"localized_catalog_code": "XXX",
"entity_code": "XXX",
"source": "XXX (last event type)",
"context": ??? (last search with term or last category view),

"event": {
"id": "12598f4980a4d55cb4af4791cc66e354"
},

"session": {
"uid": "2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2",
"vid": "55779ebd-9f1f-3ca8-dabf-0d2d83306f32"
},

"group_id": "0",
"ab_campaigns": [
{
"id": 1,
"scenario": "A"
}
]
}
</pre> |
| Product view | <pre>
{
  "page": {
    "type": {
      "identifier": "catalog_product_view"
    },
    "product": {
      "id": 66004,
      "label": "Bouteille limonade 1l",
      "sku": "0383471"
    }
  }
}
</pre> | <pre>
{
  "event_type": "view",
  "metadata_code": "product",
  "localized_catalog_code": "com_fr",
  "entity_code": "AB512"
}
</pre> |
| Category view |  <pre>
{
  "page": {
    "type": {
      "identifier": "catalog_category_view"
    },
    "category": {
      "id": 683,
      "label": "Tronçonneuse, élagueuse",
      "path": "1/550/656/679/683",
      "breadcrumb": "Jardin et aménagement extérieur|Motoculture"
    },
    "product_list": {
      "page_count": 1,
      "product_count": 1,
      "current_page": 1,
      "sort_order": "position",
      "sort_direction": "asc",
      "display_mode": "grid",
      "filters": [
        {
          "name": "facet_puissance",
          "value": "2-3"
        }
      ]
    }
  }
}
</pre> | <pre>
{
  "event_type": "view",
  "metadata_code": "category",
  "localized_catalog_code": "com_fr",
  "entity_code": "cat_1",

"list_item_count": 72,
"list_current_page": 1,
"list_page_count": 6,
"list_sort_order": "position",
"list_sort_direction": "asc",
"list_display_mode": "grid",
"list_filters": [
{
"name": "facet_puissance",
"value": "2-3"
}
]

// item details manage in display
}
</pre> |
| Search result |  <pre>
{
  "type": {
    "identifier": "catalogsearch_result_index"
  },
  "page": {
    "product_list": {
      "page_count": 1,
      "current_page": 1,
      "product_count": 1
    },
    "search": {
      "query": "0383471",
      "is_spellchecked": false
    }
  }
}
</pre>|<pre>
{
  "event_type": "search",
  "metadata_code": "product",
  "localized_catalog_code": "com_fr",

"is_spellchecked": false,
"search_query_text": "blop",
"search_query_id": "128",

"list_item_count": 72,
"list_current_page": 1,
"list_page_count": 6,
"list_sort_order": "position",
"list_sort_direction": "asc",
"list_display_mode": "grid",
"list_filters": [
{
"name": "facet_puissance",
"value": "2-3"
}
]

// item details manage in display
}
</pre> |
| Add to cart |  <pre>
{
  "page": {
    "type": {
      "identifier": "add_to_cart"
    },
    "cart": {
      "product_id": 1234
    }
  }
}
</pre>|<pre>
{
  "event_type": "add_to_cart",
  "metadata_code": "product",
  "localized_catalog_code": "com_fr",
  "entity_code": "AB512",
  "cart_qty" 2
}
</pre> |
| Display | |<pre>
{
  "event_type": "display",
  "metadata_code": "product",
  "localized_catalog_code": "com_fr",
  "entity_code": "AB512",
  "source": "search (category | autocomplete | cart | ...)"
  "position": 2
}
</pre> |
| Order |  <pre>
{
  "page": {
    "type": {
      "identifier": "checkout_index_success"
    },
    "order": {
      "subtotal": 227.4,
      "discount_total": -65.4,
      "shipping_total": 0.0,
      "grand_total": 162.0,
      "shipping_method": "owsh1_pickup_in_store",
      "payment_method": "etep_cb",
      "salesrules": "2204",
      "items": [
        {
          "sku": "2703330",
          "product_id": "899400",
          "qty": 1.0,
          "price": 2.5,
          "row_total": 2.5,
          "label": "Carte fidélité LaMaison.fr",
          "salesrules": "",
          "category_ids": [
            1680
          ],
          "date": "2025-05-06 07:58:19"
        }
      ]
    }
  }
}
</pre>|<pre>
{
  "@timestamp": "2025-05-02T08:53:05Z",

"event_type": "order",
"metadata_code": "product",
"localized_catalog_code": "com_fr",
"entity_code": "AB512",

"order": {
"order_id": 125,
"price": 12.5,
"qty": 3,
"row_total": 37.5
}
}
</pre> |
}}

h3. Delete data

* page.resolution
* page.type: [ label ]
* page.url
* page.site
* page.title
* page.store_id
* page.locale
* page.product [ id, label ]
* page.utm_...
* page.category [ label, path ]
* page.referrer
* page.order [ discount_total, payment_method, salesrules, shipping_method, shipping_total, subtotal, discount_total, items ]
* page.search [ is_redirect, redirect_url ]

h2. Session Aggregation Strategy

Use OpenSearch Transform for real-time session aggregation instead of cron jobs.
https://docs.opensearch.org/latest/im-plugin/index-transforms/index/

h2. Session index

Session aggregation structure based on elasticsuite session mapping:

<pre>
{
  "@timestamp": "2025-05-02T08:53:05Z",

  "localized_catalog_code": "com_fr",

  "start_time": "2025-05-02T08:00:00Z",
  "end_time": "2025-05-02T08:30:00Z",

  "searches": [
    {
        "metadata_code": "product"
        "query": "smartphone",
        "results_count": 45,
    }
  ],
  views: [
    {
        "metadata_code": "product"
        "count": 2
        "items": ["AB512", "CD789"]
    },
    {
        "metadata_code": "category"
        "count": 1
        "items": ["cat_1"]
    }
  ]
  cart: [
    {
        "metadata_code": "product"
        "count": 2
        "items": ["AB512", "CD789"]
    }
  ],
  order: [
    {
        "metadata_code": "product"
        "count": 2
        "items": ["AB512", "CD789"]
    }
  ],

  "group_id": "0",
  "session": {
    "uid": "2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2",
    "vid": "55779ebd-9f1f-3ca8-dabf-0d2d83306f32"
  },
  "ab_campaigns": [{"id": 1, "scenario": "A"}]
}
</pre>

Managed by OpenSearch Transform for real-time aggregation.

h2. Behavioral Data Index Strategy

*Yes, a dedicated @stats@ index is recommended for performance and maintainability.*

- Use an Elasticsearch transform on the @event@ index.
- Group by product_id + day.
- Aggregate metrics: @sum(views)@, @sum(sales)@, @sum(revenue)@.

Example (per product/day):
<pre>
{ "product_id": "9083", "date": "2025-09-11", "views": 3, "sales": 1, "revenue": 49.99, "localized_catalog": "com_fr" }
</pre>

- Query @stats@ with @date_histogram@ + @moving_fn@ to compute totals, daily, weekly metrics.
- Assemble the response in the desired nested structure.
- Inject into product docs at indexing time under @_stats@.

Example (final product doc snippet):
<pre>
"_stats": {
    "views": { "total": 15, "daily": { "count": 15, "ma": 2 }, "weekly": { "count": 15, "ma": 2.25 } },
    "sales": { ... },
    "conversion_rate": { ... },
    "revenue": { ... }
}
</pre>





*Analyze: #1323626*

+Technical specifications:+
* Create new api endpoint to send event data to Gally
* Add a message queue system to save the event data in database
* Add a message handler that will read event from message queue, validate them, reformat them (explode display and order product in separate messages)
  ** Add system to be able to extends validators (tag iterator ?)
  ** Add consumer service if not exist
  ** Validation error are managed in a specific log file
* Bulk the event in a event index (with timeseries activated, check data format below)
* Manage index rotation with ILM / Datastream
* Add sample data to have basic events in opensearch
* Create new api endpoint to send aggregated KPI to dashboard
  ** The number of searches done by users
  ** The number of pages viewed
  ** The number of category viewed
  ** The number of product views
  ** The number of products added to cart
  ** The number of sales
  ** The number of visitors

{{collapse(View details...)
|_. Request type |_. Elasticsuite |_. Gally proposal |
| Common data | <pre>
{
"event_id": "12598f4980a4d55cb4af4791cc66e354",
"date": "2025-05-02 08:53:05",
"created_at": "2025-05-02 08:53:05",
"is_invalid": false,
"page": {
"store_id": 3,
"type": {
"identifier": "catalogsearch_result_index",
"label": "Quick Search Form"
},
"locale": "fr_FR",
"site": "demo.fr",
"url": "\/etep\/payment\/redirect\/",
"title": "Test",
"resolution": {
"x": 1920,
"y": 1032
},
"referrer": {
"domain": "demo.fr",
"page": "\/checkout\/cart\/"
}
},
"session": {
"uid": "2eaaf2d5-035f-777c-e24c-2ed081eed187",
"vid": "e0d32f3e-b328-0c37-4e62-72337ed51010"
},
"customer": {
"group_id": 36
},
"ab_campaigns": [
{
"id": 1,
"scenario": "A"
}
]
}
</pre> | <pre>
{
  "@timestamp": "2025-05-02T08:53:05Z",

"event_type": "XXX",
"metadata_code": "XXX",
"localized_catalog_code": "XXX",
"entity_code": "XXX",
"source": "XXX (last event type)",
"context": ??? (last search with term or last category view),

"event": {
"id": "12598f4980a4d55cb4af4791cc66e354"
},

"session": {
"uid": "2a9c9f2d-0aff-5c1c-b0a8-98cb6460a1d2",
"vid": "55779ebd-9f1f-3ca8-dabf-0d2d83306f32"
},

"group_id": "0",
"ab_campaigns": [
{
"id": 1,
"scenario": "A"
}
]
}
</pre> |
| Product view | <pre>
{
  "page": {
    "type": {
      "identifier": "catalog_product_view"
    },
    "product": {
      "id": 66004,
      "label": "Bouteille limonade 1l",
      "sku": "0383471"
    }
  }
}
</pre> | <pre>
{
  "event_type": "view",
  "metadata_code": "product",
  "localized_catalog_code": "com_fr",
  "entity_code": "AB512"
}
</pre> |
| Category view |  <pre>
{
  "page": {
    "type": {
      "identifier": "catalog_category_view"
    },
    "category": {
      "id": 683,
      "label": "Tronçonneuse, élagueuse",
      "path": "1/550/656/679/683",
      "breadcrumb": "Jardin et aménagement extérieur|Motoculture"
    },
    "product_list": {
      "page_count": 1,
      "product_count": 1,
      "current_page": 1,
      "sort_order": "position",
      "sort_direction": "asc",
      "display_mode": "grid",
      "filters": [
        {
          "name": "facet_puissance",
          "value": "2-3"
        }
      ]
    }
  }
}
</pre> | <pre>
{
  "event_type": "view",
  "metadata_code": "category",
  "localized_catalog_code": "com_fr",
  "entity_code": "cat_1",

"list_item_count": 72,
"list_current_page": 1,
"list_page_count": 6,
"list_sort_order": "position",
"list_sort_direction": "asc",
"list_display_mode": "grid",
"list_filters": [
{
"name": "facet_puissance",
"value": "2-3"
}
]

// item details manage in display
}
</pre> |
| Search result |  <pre>
{
  "type": {
    "identifier": "catalogsearch_result_index"
  },
  "page": {
    "product_list": {
      "page_count": 1,
      "current_page": 1,
      "product_count": 1
    },
    "search": {
      "query": "0383471",
      "is_spellchecked": false
    }
  }
}
</pre>|<pre>
{
  "event_type": "search",
  "metadata_code": "product",
  "localized_catalog_code": "com_fr",

"is_spellchecked": false,
"search_query_text": "blop",
"search_query_id": "128",

"list_item_count": 72,
"list_current_page": 1,
"list_page_count": 6,
"list_sort_order": "position",
"list_sort_direction": "asc",
"list_display_mode": "grid",
"list_filters": [
{
"name": "facet_puissance",
"value": "2-3"
}
]

// item details manage in display
}
</pre> |
| Add to cart |  <pre>
{
  "page": {
    "type": {
      "identifier": "add_to_cart"
    },
    "cart": {
      "product_id": 1234
    }
  }
}
</pre>|<pre>
{
  "event_type": "add_to_cart",
  "metadata_code": "product",
  "localized_catalog_code": "com_fr",
  "entity_code": "AB512",
  "cart_qty" 2
}
</pre> |
| Display | |<pre>
{
  "event_type": "display",
  "metadata_code": "product",
  "localized_catalog_code": "com_fr",
  "entity_code": "AB512",
  "source": "search (category | autocomplete | cart | ...)"
  "position": 2
}
</pre> |
| Order |  <pre>
{
  "page": {
    "type": {
      "identifier": "checkout_index_success"
    },
    "order": {
      "subtotal": 227.4,
      "discount_total": -65.4,
      "shipping_total": 0.0,
      "grand_total": 162.0,
      "shipping_method": "owsh1_pickup_in_store",
      "payment_method": "etep_cb",
      "salesrules": "2204",
      "items": [
        {
          "sku": "2703330",
          "product_id": "899400",
          "qty": 1.0,
          "price": 2.5,
          "row_total": 2.5,
          "label": "Carte fidélité LaMaison.fr",
          "salesrules": "",
          "category_ids": [
            1680
          ],
          "date": "2025-05-06 07:58:19"
        }
      ]
    }
  }
}
</pre>|<pre>
{
  "@timestamp": "2025-05-02T08:53:05Z",

"event_type": "order",
"metadata_code": "product",
"localized_catalog_code": "com_fr",
"entity_code": "AB512",

"order": {
"order_id": 125,
"price": 12.5,
"qty": 3,
"row_total": 37.5
}
}
</pre> |
}}

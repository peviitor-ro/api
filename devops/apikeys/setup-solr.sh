#!/bin/bash
# ============================================================================
# Solr setup for company API keys and the company panel (/v7/)
# ============================================================================
# Idempotent: safe to run again. Needs SolrCloud (collections API).
#
#   SOLR_URL=http://localhost:8983/solr SOLR_USER=solr SOLR_PASS=... \
#     bash devops/apikeys/setup-solr.sh
#
# Creates (if missing):
#   - collection 'apikeys' (own configset) + fields. It holds all docs of the
#     feature, told apart by 'type': key, authorization (panel email), token (login link)
#   - field 'source' in collection 'job'
# and sets type=key on key docs created before the 'type' field existed.
# ============================================================================
set -euo pipefail

SOLR_URL="${SOLR_URL:-http://localhost:8983/solr}"
AUTH=()
if [ -n "${SOLR_USER:-}" ]; then
    AUTH=(-u "$SOLR_USER:${SOLR_PASS:-}")
fi

solr() {
    curl -s "${AUTH[@]}" "$@"
}

create_collection() {
    local name=$1
    if solr "$SOLR_URL/admin/collections?action=LIST&wt=json" | grep -q "\"$name\""; then
        echo "collection $name: exists"
        return
    fi
    if ! solr "$SOLR_URL/admin/configs?action=LIST&wt=json" | grep -q "\"$name\""; then
        solr -X POST "$SOLR_URL/admin/configs?action=CREATE&name=$name&baseConfigSet=_default&wt=json" > /dev/null
    fi
    solr -X POST "$SOLR_URL/admin/collections?action=CREATE&name=$name&numShards=1&replicationFactor=1&collection.configName=$name&wt=json" > /dev/null
    echo "collection $name: created"
}

add_field() {
    local collection=$1 field=$2 type=$3
    if solr "$SOLR_URL/$collection/schema/fields/$field?wt=json" | grep -q '"field"'; then
        echo "  $collection.$field: exists"
        return
    fi
    solr -X POST -H 'Content-Type: application/json' "$SOLR_URL/$collection/schema" \
        -d "{\"add-field\":{\"name\":\"$field\",\"type\":\"$type\",\"stored\":true,\"indexed\":true}}" > /dev/null
    echo "  $collection.$field: added ($type)"
}

create_collection apikeys
for f in type prefix cif company created_by note email ip; do add_field apikeys $f string; done
add_field apikeys active boolean
for f in created_at revoked_at expires_at used_at; do add_field apikeys $f pdate; done

# Key docs from before the 'type' field: they have a prefix but no type
untyped=$(solr "$SOLR_URL/apikeys/select?q=prefix:*%20AND%20-type:*&fl=id&rows=10000&wt=csv&csv.header=false" | tr -d '\r')
if [ -n "$untyped" ]; then
    updates=$(printf '%s\n' $untyped | sed 's/.*/{"id":"&","type":{"set":"key"}}/' | paste -sd, -)
    solr -X POST -H 'Content-Type: application/json' "$SOLR_URL/apikeys/update?commit=true" -d "[$updates]" > /dev/null
    echo "  apikeys: type=key set on $(printf '%s\n' $untyped | wc -l) old key docs"
fi

add_field job source string

echo "done"

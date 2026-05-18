# Usage:
#   awk -v tables="sales_order,sales_order_item" \
#     -f scripts/ecom7/extract-selected-tables.awk source.sql > selected.sql
#
# The dump keeps each table in one contiguous section. Preserve the global
# preamble and epilogue, but emit table bodies only for the requested names.
BEGIN {
    split(tables, requested, ",")
    for (i in requested) {
        wanted[requested[i]] = 1
    }

    in_table = 0
    emit_table = 0
    saw_table = 0
}

/^-- Table structure for table `/ {
    in_table = 1
    saw_table = 1
    table = $0
    sub(/^-- Table structure for table `/, "", table)
    sub(/`$/, "", table)
    emit_table = wanted[table]
}

/^-- Dump completed on / {
    in_table = 0
    emit_table = 0
}

{
    if (!saw_table || emit_table || !in_table) {
        print
    }
}

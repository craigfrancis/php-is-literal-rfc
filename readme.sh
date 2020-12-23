#!/bin/bash

cat "readme.md" \
  | sed -E \
    -e 's/^# (.*)/====== \1 ======/' \
    -e 's/^## (.*)/===== \1 =====/' \
    -e 's/^### (.*)/==== \1 ====/' \
    -e 's/^\* /  * /' \
    -e 's/`/\/\//g' \
    -e 's/^    /  /g' \
    -e 's/\[([^]]+)\]\(([^\)]+)\)/\[\[\2|\1\]\]/g' \
  | perl -pe 's/\[\[([^\|]+)\|\1\]\]/\1/g' \
  | sed -E -e '1h;2,$H;$!d;g' -e 's/(\nhttp[^ ]+)(\nhttp)/\1\\\\\2/g' \
  > readme.txt;

# cat "readme.txt" \
#   | sed -E \
#     -e 's/^====== (.*) ======/# \1/' \
#     -e 's/^===== (.*) =====/## \1/' \
#     -e 's/^==== (.*) ====/### \1/' \
#     -e 's/^  \* /* /' \
#     -e 's/\/\/([^\/]+)\/\//`\1`/g' \
#     -e 's/^  /    /g' \
#     -e 's/\[\[([^\|]+)\|([^]]+)\]\]/[\2](\1)/g' \
#   | perl -pe 's/^(http[^ ]+?)(\\\\)?$/[\1](\1)/g' \
#   | perl -pe 's/^\* (http[^ ]+?)(\\\\)?$/\* [\1](\1)/g' \
#   > readme.md;

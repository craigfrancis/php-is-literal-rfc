#!/bin/bash

cat "readme-v2.md" \
  | sed -E \
    -e 's/^# (.*)/====== \1 ======/' \
    -e 's/^## (.*)/===== \1 =====/' \
    -e 's/^### (.*)/==== \1 ====/' \
    -e 's/^\* /  * /' \
    -e 's/^```([a-z]+)/<code \1>/g' \
    -e 's/^```/<\/code>/g' \
    -e 's/`/\/\//g' \
    -e 's/^- /  - /g' \
    -e 's/ \*([a-z ]+)\*/ \/\/\1\/\//gi' \
    -e 's/\[([^]]+)\]\(([^\)]+)\)/\[\[\2|\1\]\]/g' \
  | perl -pe 's/\[\[([^\|]+)\|\1\]\]/\1/g' \
  | perl -0pe 's{^(.*)\R\|( *-+ *\|)+$\R?}{$1 =~ s,\|,^,gr}gme' \
  | sed -E -e '1h;2,$H;$!d;g' -e 's/(\nhttp[^ ]+)  (\nhttp)/\1\\\\\2/g' \
  | perl -pe "s/'\/\/'/'\`'/g" \
  | perl -pe "s/'\/\/\/\/'/'\`\`'/g" \
  | perl -pe "s/example\/\/(.*?)\/\//example\`\1\`/g" \
  | sed 's|////// // //////|//\`//|g' \
  | sed 's|//////// ////// ////////|//```//|g' \
  | sed 's|//////// $a = //////{$a} b////// ////////|//$a = ```{$a} b```//|g' \
  | sed 's|//////{$sql} AND category = {$category}//////|```{$sql} AND category = {$category}```|g' \
  | sed 's|////// AND name = {$name}//////|``` AND name = {$name}```|g' \
  | sed 's|//Hi ${name}//|`Hi ${name}`|g' \
  | sed 's|//////deleted ////// . ($archive ? //////IS NOT NULL////// :  //////IS NULL//////)|```deleted ``` . ($archive ? ```IS NOT NULL``` :  ```IS NULL```)|g' \
  | sed 's|  - $where_sql .=|- $where_sql .=|g' \
 > readme-v2.txt;

# cat "readme-v2.txt" \
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
#   > readme-v2.md;

pbcopy < readme-v2.txt;

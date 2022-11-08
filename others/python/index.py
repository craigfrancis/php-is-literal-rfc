#!/usr/bin/env python3.11

# Run with:
# export NAME="Example"; python3.11 index.py

# Check with:
# https://pyre-check.org/play/

# More details at:
# https://docs.python.org/3.11/library/typing.html#typing.LiteralString
# https://peps.python.org/pep-0675/
# https://www.youtube.com/watch?v=nRt_xk2MGYU

import sys
import os
import typing

#---

def run_sql(sql: typing.LiteralString, parameters: typing.List[str] = []) -> None:
  print(sql, '\n', parameters, '\n')

def placeholders(count: int) -> typing.LiteralString:
  sql = '?'
  for x in range(1, count):
    sql += ',?'
  return sql

#---

name = input('Your name: ')

#---

run_sql('WHERE name = ?', [name])

run_sql('WHERE name = ' + name) # Wrong
run_sql('WHERE name = ' + os.getenv('NAME')) # Wrong
run_sql('WHERE name = ' + sys.argv[0]) # Wrong

#---

sql = 'SELECT * FROM user WHERE deleted IS NULL'
param = []

if name != '':
  sql += ' AND name LIKE ?'
  param.append('%' + name + '%')

ids = [1, 2, 3]
if len(ids) > 0:
  sql += ' AND id IN (' + placeholders(len(ids)) + ')'
  param.extend(ids)

run_sql(sql, param)

#!/usr/bin/env python3.11

# Run with:
# export MY_USER="Example"; python3.11 index.py

# Check with:
# https://pyre-check.org/play/

# More details at:
# https://docs.python.org/3.11/library/typing.html#typing.LiteralString
# https://peps.python.org/pep-0675/
# https://www.youtube.com/watch?v=nRt_xk2MGYU

import sys
import os
import typing

#--------------------------------------------------

def run_query(sql: typing.LiteralString, parameters: typing.List[str] = []) -> None:
    print(sql)
    print(parameters)
    print('\n')

def placeholders(count: int) -> typing.LiteralString:
  sql = '?'
  for x in range(1, count):
      sql += ',?'
  return sql

#--------------------------------------------------

username = input('Enter your name: ')

#--------------------------------------------------

run_query('WHERE username = ?', [username])

run_query('WHERE username = ' + os.getenv('MY_USER'));
run_query('WHERE username = ' + sys.argv[0]);
run_query('WHERE username = ' + username);

#--------------------------------------------------

sql = 'SELECT * FROM user WHERE deleted IS NULL'
param = []

if username != '':
  sql += ' AND username LIKE ?'
  param.append('%' + username + '%');

ids = [1, 2, 3, 4, 5];
if len(ids) > 0:
  sql += ' AND u.id IN (' + placeholders(len(ids)) + ')'
  param.extend(ids)

run_query(sql, param);

# used to create the data in the mysql database from a flat text file
# called graph.txt. The database table name is assumed to be 'graph'

import argparse
import re
import pymysql
import sys
import struct

arguments = argparse.ArgumentParser()
arguments.add_argument('--inputfile',
                       default='graph.txt',
                       help='Input file containing the graph')
arguments.add_argument('--dbname',
                       default=False,
                       help='Name of database')
arguments.add_argument('--dbuser',
                       help='Name of database user',
                       required=True)
arguments.add_argument('--password',
                       required=True,
                       help='Password for database.')
args = arguments.parse_args()


db = pymysql.connect(host='localhost',
                     passwd=args.password,
                     db=args.dbname,
                     user=args.dbuser,
                     charset='utf8',
                     use_unicode=True,
                     init_command='SET NAMES UTF8')
cursor = db.cursor()
graph = []
counter = 0
regex = re.compile('(\d+) "([^"]+)" (\d+)')
with open('graph.txt', 'r') as f:
    line = f.readline().strip()
    while line:
        counter += 1
        parts = line.split('"')
        id = int(parts[0][:-1])
        name = parts[1]
        nameparts = name.split()
        lastname = nameparts[-1]
        edges = re.findall(r'(\d+) (\d+)', parts[2][1:])
        edgearray = bytearray()
        for i in range(0, len(edges)):
            a = int(edges[i][0])
            w = int(edges[i][1])
            packed = struct.pack('IH', a, w)
            edgearray.extend(packed)
        cursor.execute('INSERT INTO graph (id,name,lastname,edges) values(%s,%s,%s,%s)',
                       (id, name, lastname, edgearray))
        line = f.readline().strip()
        if counter % 10000 == 0:
            print('vertex {}'.format(counter))
            db.commit()
db.commit()

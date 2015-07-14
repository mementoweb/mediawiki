#!/usr/bin/python

import sys
import requests
import json

filename = sys.argv[1]

if len(sys.argv) > 2:
    outputfile = sys.argv[2]
else:
    outputfile = filename

print "Processing file " + filename

f = open(filename)
inputCode = f.read()
f.close()

apiURI = 'http://tools.wmflabs.org/stylize/jsonapi.php?action=stylizephp'

payload = { 'code' : inputCode }

r = requests.post(apiURI, data=payload)

j = json.loads(r.text)

g = open(outputfile, 'w')
g.write(j['stylizephp'])
g.close()

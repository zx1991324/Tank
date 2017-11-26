#!/usr/bin/env python
import os
import BaseHTTPServer
import CGIHTTPServer

# chdir(2) into the tutorial directory.
os.chdir(os.path.dirname(os.path.dirname(os.path.realpath(__file__))))
class Handler(CGIHTTPServer.CGIHTTPRequestHandler):
  cgi_directories  = ['/hackathon/hackathon/php/TankServer.php']
print "Server is running......."
BaseHTTPServer.HTTPServer(('0.0.0.0', 80), Handler).serve_forever()
#!/usr/bin/python
# 
# Simple class for feeding Carbon daemon
#
# Copyright (C) 2013 Andrea Simonetto - andrea.simonetto@cnaf.infn.it
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#

import sys, time
from socket import socket

class CarbonClient:
    def __init__(self, host, port):
        self.sock = socket()
        self.sock.connect((host, port))

    def __del__(self):
        self.sock.close()

    def insert(self, path, value, timestamp=None):
        if not timestamp:
            timestamp = int(time.time())
        self.sock.sendall("%s %s %d\n" % (path, str(value), timestamp))

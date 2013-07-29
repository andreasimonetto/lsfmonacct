
LSF monitoring & accounting
===========================

Backend
-------

The backend is composed of two parts, to follow Graphite's model/view pattern. The first
part consist of data gathering (using LSF API through PyLSF) and Carbon feeding, the second
one is an URL API to query the time series. While the former is mandatory, the latter isn't,
because you can directly use Graphite's Webapp. However, in order to use the frontend, you
also need the URL APIs.

### Data gathering

Feed Graphite with LSF's monitoring and accounting data. In order to use these scripts, 
you need to setup at least a basic [Graphite](http://graphite.readthedocs.org/) service:
* step_1
* ...
* step_n

Then you need Python support for LSF:
* git submodule init
* git submodule update
* cd pylsf/
* Follow compilation and installation instructions

For the last prerequisite (and only for accounting) you need to create a [JSON](http://www.json.org/)
file in the project's root containing the [HEP performance information](https://twiki.cern.ch/twiki/bin/view/FIOgroup/TsiBenchHEPSPECWlcg)
of your LSF hosts, called `.hostsinfo.json` and having the following format:

```JSON
{
    "hostname1": { "hs06": HepspecVal, "ncores": CoresNumber, "nslots": LsfSlots },
    "hostname2": { "hs06": HepspecVal, "ncores": CoresNumber, "nslots": LsfSlots },
    ...
}
```

**Important**: in order to decide if a job is locally or Grid submitted, we use the assumption that
when the `fromHost` field of the job begins with the string "ce", then the job's submit type is Grid,
otherwise it's locally submitted. This is an internal convention and maybe different for other sites.

### URL API

Apache Virtual Host `/etc/hosts`

```ApacheConf
# Graphite Web Basic mod_wsgi vhost
<VirtualHost *:80>
    ServerName graphite-farm.cr.cnaf.infn.it
    DocumentRoot "/usr/share/graphite/webapp"
    ErrorLog /var/log/httpd/graphite-web-error.log
    CustomLog /var/log/httpd/graphite-web-access.log common
    Alias /media/ "/usr/lib/python2.6/site-packages/django/contrib/admin/media/"

    WSGIScriptAlias / /usr/share/graphite/graphite-web.wsgi
    WSGIImportScript /usr/share/graphite/graphite-web.wsgi process-group=%{GLOBAL} application-group=%{GLOBAL}

    <Location "/content/">
        SetHandler None
    </Location>

    <Location "/media/">
        SetHandler None
    </Location>
</VirtualHost>
```

`common.inc.php`

```PHP
$WHISPER_DIR = "/var/lib/carbon/whisper/monitoring/";
$GRAPHITE_BASE_URL = "http://graphite-farm.cr.cnaf.infn.it";
$GRAPH_DEFAULT_SIZE = "800x600";
```

Frontend
--------

Awk awk awk

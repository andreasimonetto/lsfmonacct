
LSF monitoring & accounting
===========================

![Sample plot](web/image/sample.png)

Backend
-------

The backend is composed of two parts, to follow Graphite's model/view pattern. The first
part consist of data gathering (using LSF API through PyLSF) and Carbon feeding, the second
one consist of an URL API to query the time series. While the former is mandatory, the latter
isn't, because you can directly use Graphite Webapp. However, in order to use the frontend,
you also need the URL APIs.

### Data gathering

Feed Graphite with LSF's monitoring and accounting data. In order to use these scripts, you
need at least a basic [Graphite](http://graphite.readthedocs.org/) setup. Then you need 
Python support for LSF:
* git submodule init
* git submodule update
* Enter in `pylsf/` subfolder
* Follow compilation and installation instructions

The last prerequisite (only for accounting) is a [JSON](http://www.json.org/) file in the
project's root containing the [HEP performance information](https://twiki.cern.ch/twiki/bin/view/FIOgroup/TsiBenchHEPSPECWlcg)
of your LSF hosts, called `.hostsinfo.json` and having the following format:

```JSON
{
    "hostname1": { "hs06": HepspecVal, "ncores": CoresNumber, "nslots": LsfSlots },
    "hostname2": { "hs06": HepspecVal, "ncores": CoresNumber, "nslots": LsfSlots },
    ...
}
```

The scripts `monitoring_update` and `accounting_update` gather the data and feed Carbon. The first
insert monitoring data in time series prefixed with the string `monitoring.` and need to be called 
quite often (every few minutes, depending on the time intervals you set in `carbon/storage-schemas.conf`).
The second insert data in time series starting with `accounting.` and need to be called once a day
(usually right after the daily LSF `.acct` log rotation). Only `accounting_update` take parameters,
that are:
* LSF log directory (mandatory)
* List of dates to look for (optional, if omitted look for yesterday finished jobs)

**Important**: in order to decide if a job is locally or Grid submitted, we make the following assumption:
when the `fromHost` field of the job begins with the string "ce", then the job's submit type is Grid,
otherwise it's locally submitted. This is an internal convention and maybe different for other sites.

### URL API

URL APIs offer a way to display queues informations in multiple formats, hiding the complexity
of Graphite APIs. They consist in a small PHP script (`web/api.php`) that query the Graphite Webapp.
The resource part of each URL is one of those generated by the following grammar:

```
Resource  ::=  /Type/Submit/Queue/Period.Unit[-WxH].Format
Type      ::=  monitoring | accounting
Submit    ::=  grid | local | all
Queue     ::=  queue[*] | each | all
Period    ::=  date[-date] | nh | nd | nw | day | week | month | year
Unit      ::=  jobs | efficiency | sec | hs06 | pledge
Format    ::=  png | json | csv | raw 
```

where:
* square brackets means "optional part";
* *queue* is a non-empty sequence of alphanumerics and underscores;
* *date* has the form `yyyymmdd[HHMM]`, as a sequence of 8 or 12 digits;
* *n* is a positive inter

We also have the following rules, as syntactic sugar:

```
/Type/Queue/... => /Type/all/Queue/...
/Type/Period... => /Type/all/all/Period... 
```

Create Apache Virtual Host (it must be at least accessible from the server itself, for
example using an entry in `/etc/hosts`). In this example the host name is
`graphite.mysite.com`:

```ApacheConf
# Graphite Webapp using mod_wsgi
<VirtualHost *:80>
    ServerName graphite.mysite.com
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

Personalize `web/common.inc.php` with your installation parameters, for example:

```PHP
$WHISPER_DIR = "/var/lib/carbon/whisper/monitoring/";
$GRAPHITE_BASE_URL = "http://graphite.mysite.com";
$GRAPH_DEFAULT_SIZE = "800x600";
```

Finally create a Virtual Host for the base URL. In this example the project root 
is `/opt/lsfmonacct/` and the host name is `monacct.mysite.com`:

```ApacheConf
<VirtualHost *:80>
    ServerName monacct.mysite.com
    DocumentRoot "/opt/lsfmonacct/web"

    <Directory "/opt/lsfmonacct/web">
        Options Indexes FollowSymLinks
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>
</VirtualHost>
```

Frontend
--------

The frontend consist of a web interface (`web/index.php`) that uses the 
URL API in order to display time series plots. To use it, you have to:

* Configure URL API backend
* Download the latest jQuery 2.x from http://jquery.com/
* Uncompress it in `web/js` project's subfolder
* Create symbolic link <jquery-you-download.js> -> jquery
* Download the latest non-commercial jQWigets 2.x from http://www.jqwidgets.com/
* Uncompress and copy the `jqwidgets/` directory in `web/js` project's subfolder

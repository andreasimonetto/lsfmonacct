
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

For the last prerequisite (only for accounting) you need to create a [JSON](http://www.json.org/)
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
otherwise it's locally submitted. This is an internal convention and maybe different for your site.

### URL API

Bla bla bla


Frontend
--------

Awk awk awk

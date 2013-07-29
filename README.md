LSF monitoring & accounting
==========

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

    {
        "host1": { "ncores": host1_cores_number, "hs06": host1_total_hepspec06, "nslots": host1_lsf_slots },
        "host2": { "ncores": host2_cores_number, "hs06": host2_total_hepspec06, "nslots": host2_lsf_slots },
        ...
    }

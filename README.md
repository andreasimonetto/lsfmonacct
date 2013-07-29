LSF monitoring & accounting
==========

Feed Graphite with LSF's monitoring and accounting data. In order to use these scripts, 
you need to setup at least a basic Graphite service:
* step_1
* ...
* step_n

Then you need a python support for LSF:
* git submodule init
* git submodule update
* cd pylsf/
* Follow compilation and installation instructions

For the last prerequisite (only for monitoring_update) you need to create a JSON file 
with the HEP performance information of your hosts, called `.hostsinfo.json`, having
the following format:
    {
        "host1": { "ncores": host1_cores_number, "hs06": host1_total_hepspec06, "nslots": host1_lsf_slots },
        "host2": { "ncores": host2_cores_number, "hs06": host2_total_hepspec06, "nslots": host2_lsf_slots },
        ...
    }

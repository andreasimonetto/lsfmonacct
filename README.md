lsfmonacct
==========

Feed Graphite with LSF's monitoring and accounting data

    .hostsinfo.json
    {
        "host1": { "ncores": host1_cores_number, "hs06": host1_total_hepspec06, "nslots": host1_lsf_slots },
        "host2": { "ncores": host2_cores_number, "hs06": host2_total_hepspec06, "nslots": host2_lsf_slots },
        ...
    }

For PyLSF:
* git submodule init
* git submodule update
* cd pylsf/
* Follow compilation and installation instructions

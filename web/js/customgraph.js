function getPledgePeriod(period) {
    if([ "day", "week", "month", "year" ].indexOf(period) != -1)
       	return "year";
    return period.substring(0, 4) + "0101"; // + period.substring(8);
}

function graphPledgeAccounted(period, fn) {
    $.getJSON("accounting/each/" + getPledgePeriod(period) + ".pledge.json")
    .done(function(json) {
        var pledge = {};
        var pledge_tot = 0;
        $.each(json, function(i, queue) {
            var qname = queue["target"];
            var qpledge_num = queue["datapoints"].length;
            var qpledge = queue["datapoints"][qpledge_num - 1][0];
            // pledge[<queue>] = [ <accounted>, <pledged> ]
            pledge[qname] = [ 0, qpledge ];
            pledge_tot += qpledge;
        });

        $.getJSON("accounting/each/" + period + ".hs06.json")
        .done(function(json) {
            var data = [];
            var accounted_tot = 0;
            $.each(json, function(i, queue) {
                var qname = queue["target"];
                for(q in pledge) {
                    if(qname == (q + ".wct") || (!qname.match(new RegExp("_(t|tier)[2-3]:wct$")) && qname.match(new RegExp("^" + q + ".*:wct$")))) {
                        var qaccounted_num = queue["datapoints"].length;
                        if(qaccounted_num > 0) {
                            var qaccounted = 0;
                            for(i = 0; i < qaccounted_num; i++)
                                qaccounted += queue["datapoints"][i][0];
                            pledge[q][0] += qaccounted / qaccounted_num;
                        }
                    }
                }
            });

            for(var qname in pledge) {
                var accounted = pledge[qname][0];
                var pledged = pledge[qname][1];
                data.push({
                    queue     : qname.charAt(0).toUpperCase() + qname.slice(1).replace("_", " "),
                    accounted : accounted,
                    pledged   : pledged
                });
                accounted_tot += accounted;
            }

            fn(accounted_tot, pledge_tot, data);
        })
        .fail(function(jqxhr, textStatus, error) {
            var err = textStatus + ', ' + error;
            console.log( "Request Failed: " + err);
        });
    })
    .fail(function(jqxhr, textStatus, error) {
        var err = textStatus + ', ' + error;
        console.log( "Request Failed: " + err);
    });
}

function gridPledgeAccounted(period, fn) {
    $.getJSON('accounting/each/' + period + '.hs06.json')
    .done(function(json) {
        var accounted = {};
        $.each(json, function(i, queue) {
            var matches = queue['target'].match(/^(.*):(cpt|wct)$/);
            if(!matches)
                return;

            var qname = matches[1];
            var type = matches[2];
            if(!accounted[qname])
                accounted[qname] = { 'cpt' : 0.0, 'cpt_avg' : 0.0, 'wct' : 0.0, 'wct_avg' : 0.0 };

            var qaccounted = 0.0;
            var qaccounted_num = queue['datapoints'].length;
            for(var i = 0; i < qaccounted_num; i++)
                qaccounted += queue['datapoints'][i][0];
            accounted[qname][type] = qaccounted;
            if(qaccounted_num > 0)
                accounted[qname][type + '_avg'] = qaccounted / qaccounted_num;
        });

        var data = [];
        var total_cpt = 0.0, total_cpt_avg = 0.0, total_wct = 0.0, total_wct_avg = 0.0;
        for(var qname in accounted) {
            data.push({
                queue    : qname.charAt(0).toUpperCase() + qname.slice(1).replace("_", " "),
                cpt      : accounted[qname]['cpt'],
                cpt_avg  : accounted[qname]['cpt_avg'],
                wct      : accounted[qname]['wct'],
                wct_avg  : accounted[qname]['wct_avg']
            });
            total_cpt += accounted[qname]['cpt'];
            total_cpt_avg += accounted[qname]['cpt_avg'];
            total_wct += accounted[qname]['wct'];
            total_wct_avg += accounted[qname]['wct_avg'];
        }
        data.push({
            queue    : "Total",
            cpt      : total_cpt,
            cpt_avg  : total_cpt_avg,
            wct      : total_wct,
            wct_avg  : total_wct_avg
        });

        fn(data);
    })
    .fail(function(jqxhr, textStatus, error) {
        var err = textStatus + ', ' + error;
        console.log( "Request Failed: " + err);
    });
}

function graphPledges(period, fn) {
    $.getJSON("accounting/each/" + getPledgePeriod(period) + ".pledge.json")
    .done(function(json) {
        var data = [];
        var pledge = {};
        var pledge_total = 0.0;
        $.each(json, function(i, queue) {
            var qname = queue["target"];
            var qpledge_num = queue["datapoints"].length;
            var qpledge = queue["datapoints"][qpledge_num - 1][0];
            pledge[qname] = qpledge;
            pledge_total += qpledge;
        });

        for(q in pledge) {
            data.push({
                queue: q.charAt(0).toUpperCase() + q.slice(1),
                pledge: pledge[q] * 100 / pledge_total
            });
        }

        fn(data);
    })
    .fail(function(jqxhr, textStatus, error) {
        var err = textStatus + ', ' + error;
        console.log( "Request Failed: " + err);
    });
}

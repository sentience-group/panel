@extends('layouts.master')

@section('title')
    Viewing Server: {{ $server->name }}
@endsection

@section('scripts')
    @parent
    <script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.2.1/highcharts.js"></script>
    <script src="{{ asset('js/chartjs/chart.bar.js') }}"></script>
@endsection

@section('content')
<div class="col-md-9">
    @foreach (Alert::getMessages() as $type => $messages)
        @foreach ($messages as $message)
            <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {{ $message }}
            </div>
        @endforeach
    @endforeach
    <ul class="nav nav-tabs" id="config_tabs">
        <li class="active"><a href="#stats" data-toggle="tab">{{ trans('server.index.info_use') }}</a></li>
        <li id="triggerConsoleView"><a href="#console" data-toggle="tab">{{ trans('server.index.control') }}</a></li>
    </ul><br />
    <div class="tab-content">
        <div class="tab-pane active" id="stats">
            <div class="row">
                <div class="col-md-12" id="chart_memory" style="height:250px;"></div>
                <div class="col-md-12" id="chart_cpu" style="height:250px;"></div>
            </div>
            <div class="row">
                <div class="col-md-12" id="stats_players">
                    <h3 class="nopad">Active Players</h3><hr />
                    <div id="players_notice" class="alert alert-info">
                        <i class="fa fa-spinner fa-spin"></i> Waiting for response from server...
                    </div>
                    <span id="toggle_players" style="display:none;">
                        <p class="text-muted">No players are online.</p>
                    </span>
                </div>
                <div class="col-md-12">
                    <h3>{{ trans('server.index.server_info') }}</h3><hr />
                    <table class="table table-striped table-bordered table-hover">
                        <tbody>
                            <tr>
                                <td><strong>{{ trans('server.index.connection') }}</strong></td>
                                <td><code>{{ $server->ip }}:{{ $server->port }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>{{ trans('strings.node') }}</strong></td>
                                <td>{{ $node->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ trans('server.index.mem_limit') }}</strong></td>
                                <td>{{ $server->memory }} MB</td>
                            </tr>
                            <tr>
                                <td><strong>{{ trans('server.index.disk_space') }}</strong></td>
                                <td>{{ $server->disk }} MB</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="console">
            <div class="row">
                <div class="col-md-12">
                    <textarea id="live_console" class="form-control console" readonly="readonly">Loading Previous Content...</textarea>
                </div>
                <div class="col-md-6">
                    <hr />
                    <form action="#" method="post" id="console_command" style="display:none;">
                        <fieldset>
                            <div class="input-group">
                                <input type="text" class="form-control" name="command" id="ccmd" placeholder="{{ trans('server.index.command') }}" />
                                <span class="input-group-btn">
                                    <button id="sending_command" class="btn btn-primary btn-sm">&rarr;</button>
                                </span>
                            </div>
                        </fieldset>
                    </form>
                    <div class="alert alert-danger" id="sc_resp" style="display:none;margin-top: 15px;"></div>
                </div>
                <div class="col-md-6" style="text-align:center;">
                    <hr />
                    <button class="btn btn-success btn-sm disabled" data-attr="power" data-action="start">Start</button>
                    <button class="btn btn-primary btn-sm disabled" data-attr="power" data-action="restart">Restart</button>
                    <button class="btn btn-danger btn-sm disabled" data-attr="power" data-action="stop">Stop</button>
                    <button class="btn btn-danger btn-sm disabled" data-attr="power" data-action="kill"><i class="fa fa-ban" data-toggle="tooltip" data-placement="top" title="Kill Running Process"></i></button>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#pauseConsole" id="pause_console"><small><i class="fa fa-pause fa-fw"></i></small></button>
                    <div id="pw_resp" style="display:none;margin-top: 15px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pauseConsole" tabindex="-1" role="dialog" aria-labelledby="PauseConsole" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="PauseConsole">{{ trans('server.index.scrollstop') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <textarea id="paused_console" class="form-control console" readonly="readonly"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('strings.close') }}</button>
            </div>
        </div>
    </div>
</div>
<script>
$(window).load(function () {
    $('[data-toggle="tooltip"]').tooltip();

    // -----------------+
    // Charting Methods |
    // -----------------+
    $('#chart_memory').highcharts({
        chart: {
            type: 'area',
            animation: Highcharts.svg,
            marginRight: 10,
        },
        colors: [
            '#113F8C',
            '#00A1CB',
            '#01A4A4',
            '#61AE24',
            '#D0D102',
            '#D70060',
            '#E54028',
            '#F18D05',
            '#616161',
            '#32742C',
        ],
        credits: {
            enabled: false,
        },
        title: {
            text: 'Live Memory Usage',
        },
        tooltip: {
            shared: true,
            crosshairs: true,
            formatter: function () {
                var s = '<b>Memory Usage</b>';

                $.each(this.points, function () {
                    s += '<br/>' + this.series.name + ': ' +
                        this.y + 'MB';
                });

                return s;
            },
        },
        xAxis: {
            visible: false,
        },
        yAxis: {
            title: {
                text: 'Memory Usage (MB)',
            },
            plotLines: [{
                value: 0,
                width: 1,
            }],
        },
        plotOptions: {
            area: {
                fillOpacity: 0.10,
                marker: {
                    enabled: false,
                },
            },
        },
        legend: {
            enabled: false
        },
        series: [{
            name: 'Total Memory',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }]
    });

    $('#chart_cpu').highcharts({
        chart: {
            type: 'area',
            animation: Highcharts.svg,
            marginRight: 10,
        },
        colors: [
            '#113F8C',
            '#00A1CB',
            '#01A4A4',
            '#61AE24',
            '#D0D102',
            '#D70060',
            '#E54028',
            '#F18D05',
            '#616161',
            '#32742C',
        ],
        credits: {
            enabled: false,
        },
        title: {
            text: 'Live CPU Usage',
        },
        tooltip: {
            shared: true,
            crosshairs: true,
            formatter: function () {
                var s = '<b>CPU Usage</b>';

                $.each(this.points, function () {
                    s += '<br/>' + this.series.name + ': ' +
                        this.y + '%';
                });

                return s;
            },
        },
        xAxis: {
            visible: false,
        },
        yAxis: {
            title: {
                text: 'CPU Usage (%)',
            },
            plotLines: [{
                value: 0,
                width: 1,
            }],
        },
        plotOptions: {
            area: {
                fillOpacity: 0.10,
                stacking: 'normal',
                lineWidth: 1,
                marker: {
                    enabled: false,
                },
            },
        },
        legend: {
            enabled: true
        },
        series: [{
            name: 'Total CPU',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }]
    });

    // Socket Recieves New Server Stats
    socket.on('proc', function (proc) {
        var MemoryChart = $('#chart_memory').highcharts();
        MemoryChart.series[0].addPoint(parseInt(proc.data.memory.total / (1024 * 1024)), true, true);

        var CPUChart = $('#chart_cpu').highcharts();

        if({{ $server->cpu }} > 0) {
            CPUChart.series[0].addPoint(parseFloat(((proc.data.cpu.total / {{ $server->cpu }}) * 100).toFixed(3).toString()), true, true);
        } else {
            CPUChart.series[0].addPoint(proc.data.cpu.total, true, true);
        }
        for (i = 0, length = proc.data.cpu.cores.length; i < length; i++) {
            if (typeof CPUChart.series[ i + 1 ] === 'undefined') {
                CPUChart.addSeries({
                    name: 'Core ' + i,
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                });
            }
            if({{ $server->cpu }} > 0) {
                CPUChart.series[ i + 1 ].addPoint(parseFloat(((proc.data.cpu.cores[i] / {{ $server->cpu }}) * 100).toFixed(3).toString())
, true, true);
            } else {
                CPUChart.series[ i + 1 ].addPoint(proc.data.cpu.cores[i], true, true);
            }
        }
    });

    // Socket Recieves New Query
    socket.on('query', function (data){
        if($('#players_notice').is(':visible')){
            $('#players_notice').hide();
            $('#toggle_players').show();
        }
        if(data['data'].players != undefined && data['data'].players.length !== 0){
            $('#toggle_players').html('');
            $.each(data['data'].players, function(id, d) {
                $('#toggle_players').append('<code>' + d.name + '</code>,');
            });
        }else{
            $('#toggle_players').html('<p class=\'text-muted\'>No players are currently online.</p>');
        }
    });

    // New Console Data Recieved
    socket.on('console', function (data) {
        console.log(JSON.stringify(data));
        $('#live_console').val($('#live_console').val() + data.line);
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });

    // Update Listings on Initial Status
    socket.on('initial_status', function (data) {
        if (data.status !== 0) {
            $.ajax({
                type: 'GET',
                headers: {
                    'X-Access-Token': '{{ $server->daemonSecret }}',
                    'X-Access-Server': '{{ $server->uuid }}'
                },
                url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/log',
                timeout: 10000
            }).done(function(data) {
                $('#live_console').val(data);
                $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                alert('Unable to load initial server log, try reloading the page.');
            });
        } else {
            $('#live_console').val('Server is currently off.');
        }
        updateServerPowerControls(data.status);
        updatePlayerListVisibility(data.status);
    });

    // Update Listings on Status
    socket.on('status', function (data) {
        updateServerPowerControls(data.status);
        updatePlayerListVisibility(data.status);
    });

    // Scroll to the top of the Console when switching to that tab.
    $('#triggerConsoleView').click(function () {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });
    if($('triggerConsoleView').is(':visible')) {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    }
    $('a[data-toggle=\'tab\']').on('shown.bs.tab', function (e) {
        $('#live_console').scrollTop($('#live_console')[0].scrollHeight);
    });

    // Load Paused Console with Live Console Data
    $('#pause_console').click(function(){
        $('#paused_console').val($('#live_console').val());
    });

    function updatePlayerListVisibility(data) {
        // Server is On or Starting
        if(data !== 0) {
            $('#stats_players').show();
        } else {
            $('#stats_players').hide();
        }
    }
    @can('command', $server)
        // Send Command to Server
        $('#console_command').submit(function (event) {

            event.preventDefault();
            var ccmd = $('#ccmd').val();
            if (ccmd == '') {
                return;
            }

            $('#sending_command').html('<i class=\'fa fa-refresh fa-spin\'></i>').addClass('disabled');
            $.ajax({
                type: 'POST',
                headers: {
                    'X-Access-Token': '{{ $server->daemonSecret }}',
                    'X-Access-Server': '{{ $server->uuid }}'
                },
                contentType: 'application/json; charset=utf-8',
                url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/command',
                timeout: 10000,
                data: JSON.stringify({ command: ccmd })
            }).fail(function (jqXHR) {
                $('#sc_resp').html('Unable to process your request. Please try again.').fadeIn().delay(5000).fadeOut();
            }).always(function () {
                $('#sending_command').html('&rarr;').removeClass('disabled');
                $('#ccmd').val('');
            });
        });
    @endcan
    @can('power', $server)
        var can_run = true;
        function updateServerPowerControls (data) {

            // Reset Console Data
            if (data === 2) {
                $("#live_console").val('');
            }

            // Server is On or Starting
            if(data == 1 || data == 2) {
                $("#console_command").slideDown();
                $('[data-attr="power"][data-action="start"]').addClass('disabled');
                $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').removeClass('disabled');
            } else {
                $("#console_command").slideUp();
                $('[data-attr="power"][data-action="start"]').removeClass('disabled');
                $('[data-attr="power"][data-action="stop"], [data-attr="power"][data-action="restart"]').addClass('disabled');
            }

            if(data !== 0) {
                $('[data-attr="power"][data-action="kill"]').removeClass('disabled');
            } else {
                $('[data-attr="power"][data-action="kill"]').addClass('disabled');
            }

        }

        $('[data-attr="power"]').click(function (event) {
            event.preventDefault();
            var action = $(this).data('action');
            if (action === 'kill') {
                var killConfirm = confirm('WARNING: This operation will not save your server data gracefully. You should only use this if your server is failing to respond to normal stop commands.');
            } else { var killConfirm = true; }

            if(killConfirm) {
                $.ajax({
                    type: 'PUT',
                    headers: {
                        'X-Access-Token': '{{ $server->daemonSecret }}',
                        'X-Access-Server': '{{ $server->uuid }}'
                    },
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({
                        action: action
                    }),
                    url: '{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/server/power',
                    timeout: 10000
                }).done(function(data) {
                    $('#pw_resp').attr('class', 'alert alert-success').html('Server has been ' + action + 'ed successfully.').fadeIn().delay(5000).fadeOut();
                }).fail(function(jqXHR) {
                    var error = 'An unknown error occured processing this request.';
                    if (typeof jqXHR.responseJSON.error !== 'undefined') {
                        error = jqXHR.responseJSON.error;
                    }
                    $('#pw_resp').attr('class', 'alert alert-danger').html('Unable to process your request. Please try again. (' + error + ')').fadeIn().delay(5000).fadeOut();
                });
            }

        });

    @endcan
});

$(document).ready(function () {
    $('.server-index').addClass('active');
});
</script>
@endsection
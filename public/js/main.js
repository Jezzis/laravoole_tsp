var canvas, ctx;
var WIDTH, HEIGHT;
var points = [];
var running;
var canvasMinX, canvasMinY;
var doPreciseMutate;

var POPULATION_SIZE;
var ELITE_RATE;
var CROSSOVER_PROBABILITY;
var MUTATION_PROBABILITY;
var UNCHANGED_GENS;

var mutationTimes;
var dis;
var bestValue, best;
var currentGeneration;
var currentBest;
var population;
var values;
var fitnessValues;
var roulette;

var sock = null;
var wsuri = "ws://localhost:9050/";

window.onload = function () {

    console.log('onload start...');

    sock = new WebSocket(wsuri);

    sock.onopen = function () {
        console.log('connected to ' + wsuri);

        init();
    };

    sock.onclose = function (e) {
        console.log('connection closed (' + e.code + ')');
    };

    sock.onmessage = function (e) {
        var resp = JSON.parse(e.data);
        if (resp.error) {
            error = JSON.parse(resp.error);
            console.info(error);
            return;
        }

        console.debug(resp);
        var content = resp.result;
        if (content.cb) {
            var callback = 'cb' + content.cb.ucfirst();
            try {
                eval(callback + "('" + JSON.stringify(content.p) + "');")
            } catch (e) {
                console.error(e.message);
            }
        }
    }
};

function send(route, params) {
    params = params || {};
    var data = {
        method: route,
        params: params
    };
    sock.send(JSON.stringify(data) + "\n\r");
}

$(function () {
    $('#addRandom_btn').click(function () {
        addRandomPoints(10);
    });
    $('#start_btn').click(function () {
        doStop();
        doStart();
    });
    $('#clear_btn').click(function () {
        doStop();
        doClearPoints();
        doInit(WIDTH, HEIGHT);
        clearCanvas();
    });
    $('#stop_btn').click(function () {
        doStop();
    });
});


function init() {
    ctx = $('#canvas')[0].getContext("2d");
    WIDTH = $('#canvas').width();
    HEIGHT = $('#canvas').height();
    doInit(WIDTH, HEIGHT);
    init_mouse();
}

function init_mouse() {
    $("canvas").click(function (evt) {
        if (!running) {
            canvasMinX = $("#canvas").offset().left;
            canvasMinY = $("#canvas").offset().top;
            $('#status').text("");

            x = evt.pageX - canvasMinX;
            y = evt.pageY - canvasMinY;
            doAddPoint(x, y);
        }
    });
}

function addRandomPoints(number) {
    doAddRandomPoints(number);
}

function drawCircle(point) {
    ctx.fillStyle = '#000';
    ctx.beginPath();
    ctx.arc(point.x, point.y, 3, 0, Math.PI * 2, true);
    ctx.closePath();
    ctx.fill();
}

function drawLines(array) {
    ctx.strokeStyle = '#f00';
    ctx.lineWidth = 1;
    ctx.beginPath();

    ctx.moveTo(array[0].x, array[0].y);
    for (var i = 1; i < array.length; i++) {
        ctx.lineTo(array[i].x, array[i].y)
    }
    ctx.lineTo(array[0].x, array[0].y);

    ctx.stroke();
    ctx.closePath();
}

/** 发送消息 */
function doInit(width, height) {
    send('index/init', {'w': width, 'h': height});
}

function doStart() {
    send('index/start');
}

function doStop() {
    send('index/stop');
}

function doAddPoint(x, y) {
    doStop();
    send('index/addPoint', {'x': x, 'y': y});
}

function doAddRandomPoints(num) {
    doStop();
    send('index/addRandomPoints', {'num': num});
}

function doClearPoints() {
    send('index/clearPoints');
}

/** 服务器端消息回调处理 */
function cbUpdateStatus(params) {
    params = JSON.parse(params);
    if (params.status) {
        running = status == 'running';
    }

    if (params.msg) {
        $('#status').text(params.msg);
    }
}

function cbUpdatePoints(params)
{
    params = JSON.parse(params);
    $('#status').text("There are " + params.points.length + " Cities in the map. ");
    draw(params.points, []);
}

function cbSyncSolution(params) {
    params = JSON.parse(params);
    $('#status').text("There are " + params.points.length + " cities in the map, "
        + "the " + params.currentGeneration + "th generation with "
        + params.mutationTimes + " times of mutation. best value: "
        + ~~(params.bestValue));

    draw(params.points, params.best);
}

function draw(points, best) {
    clearCanvas();
    if (points.length > 0) {
        for (var i = 0; i < points.length; i++) {
            drawCircle(points[i]);
        }
        if (best.length === points.length) {
            drawLines(best);
        }
    }
}

function clearCanvas() {
    ctx.clearRect(0, 0, WIDTH, HEIGHT);
}

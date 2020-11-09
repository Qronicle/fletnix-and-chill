var Drawing = {

    MODE_DRAWING: 'drawing',
    MODE_IDLE: 'idle',
    MODE_VIEW: 'view',

    TOOL_BRUSH: 'brush',
    TOOL_BUCKET: 'bucket',

    UPDATE_TYPE_PENCIL: 'pencil',

    $canvas: null,
    canvas: null,
    sprite: null,

    canvasWidth: 0,
    canvasHeight: 0,
    canvasRatio: 1,

    mode: null,
    tool: null,
    color: [0,0,0],
    brushSize: 5,
    toolsUpdated: false,

    pointsCache: [],
    imageCache: [],

    tempCanvas: null,
    newPointMinDist: 4,

    onUpdate: function(data){}, // Pls override

    init: function ($canvas) {
        Drawing.mode = Drawing.MODE_IDLE;
        Drawing.tool = Drawing.TOOL_BRUSH;

        Drawing.canvas = $canvas[0];
        Drawing.$canvas = $canvas;
        Drawing.sprite = Drawing.canvas.getContext("2d");

        var pixelRatio = window.devicePixelRatio || 1;
        //var rect = Drawing.canvas.getBoundingClientRect();

        Drawing.canvasWidth = 1000;
        Drawing.canvasHeight = 1000;
        var size = $canvas.parent().width();
        Drawing.$canvas.css({height: size, width: size});
        Drawing.canvas.width = size * pixelRatio;
        Drawing.canvas.height = size * pixelRatio;
        var canvasPixelRatio = Drawing.canvas.width / Drawing.canvasWidth;
        Drawing.sprite.scale(canvasPixelRatio, canvasPixelRatio);
        Drawing.canvasRatio = Drawing.canvasWidth / size;

        Drawing.tempCanvas = document.createElement('canvas');
        Drawing.tempCanvas.width = Drawing.canvas.width;
        Drawing.tempCanvas.height = Drawing.canvas.height;

        Drawing.sprite.lineCap = 'round';
        Drawing.sprite.lineJoin = 'round';
        Drawing.sprite.imageSmoothingEnabled = true;
        Drawing.canvas.imageSmoothingEnabled = true;

        Drawing.resetTools();

        // Callbacks
        document.onmouseup = Drawing.onMouseUp;
        Drawing.canvas.onmouseout = Drawing.onMouseOut;
        Drawing.canvas.onmouseover = Drawing.onMouseOver;
        Drawing.canvas.onmousedown = Drawing.onMouseDown;
        Drawing.canvas.onmousemove = Drawing.onMouseMove;
        Drawing.canvas.ontouchstart = Drawing.onMouseDown;
        Drawing.canvas.ontouchmove = Drawing.onMouseMove;
        Drawing.canvas.ontouchend = Drawing.onMouseUp;
    },

    /** CallBacks *************************************************************************************************/

    onMouseUp: function (e) {
        if (Drawing.mode === Drawing.MODE_VIEW) {
            return false;
        }
        Drawing.getTool().end(Drawing.pointFromEvent(e), e.target == Drawing.canvas);
    },

    onMouseOut: function (e) {
        if (Drawing.mode === Drawing.MODE_VIEW) {
            return false;
        }
        var offset = Drawing.$canvas.offset();
        var point = new Point(
            (e.clientX - offset.left) * Drawing.canvasRatio,
            (e.clientY - offset.top) * Drawing.canvasRatio
        );
        Drawing.getTool().exit(point);
    },

    onMouseOver: function (e) {
        if (Drawing.mode === Drawing.MODE_VIEW) {
            return false;
        }
        Drawing.getTool().enter(Drawing.pointFromEvent(e));
    },

    onMouseDown: function (e) {
        if (Drawing.mode === Drawing.MODE_VIEW) {
            return false;
        }
        Drawing.getTool().start(Drawing.pointFromEvent(e));
    },

    onMouseMove: function (e) {
        if (Drawing.mode === Drawing.MODE_VIEW) {
            return false;
        }
        Drawing.getTool().move(Drawing.pointFromEvent(e));
    },

    /** Tools *****************************************************************************************************/

    getTool: function() {
        return Tools[Drawing.tool];
    },

    setTool: function(tool) {
        Drawing.tool = tool;
        Drawing.getTool().init();
        Drawing.toolsUpdated = true;
    },

    setBrushSize: function(size) {
        Drawing.brushSize = size;
        Drawing.sprite.lineWidth = size;
        Drawing.toolsUpdated = true;
    },

    setColor: function(color) {
        Drawing.color = color;
        for (var c = 0; c < Drawing.color.length; c++) {
            Drawing.color[c] = parseInt(Drawing.color[c]);
        }
        Drawing.sprite.strokeStyle = 'rgb(' + color.join(',') + ')';
        Drawing.toolsUpdated = true;
    },

    resetTools: function() {
        Drawing.setColor([0,0,0]);
        Drawing.setBrushSize(5);
        Drawing.setTool(Drawing.TOOL_BRUSH);
        Drawing.toolsUpdated = true;
    },

    /** Drawing methods *******************************************************************************************/

    flushPointsCache: function () {
        if (Drawing.mode === Drawing.MODE_VIEW) {
            return false;
        }
        //console.log(JSON.stringify(Drawing.pointsCache));
        if (Drawing.pointsCache.length) {
            data = [];
            if (Drawing.toolsUpdated) {
                data.push(Drawing.getTool().toArray());
                Drawing.toolsUpdated = false;
                Drawing.imageCache.push(data[0]);
            }
            data.push(Drawing.pointsCache);
            Drawing.$canvas.trigger('update', [data]);
        }
        Drawing.imageCache.push(Drawing.pointsCache);
        Drawing.pointsCache = [];
    },

    startLine: function (point) {
        Drawing.pointsCache.push(point);
        Drawing.startLineAtPoint(point);
    },

    startLineAtPoint: function (point) {
        Drawing.sprite.beginPath();
        Drawing.sprite.moveTo(point.x, point.y);
    },

    endLine: function (point) {
        if (!Drawing.useNewPoint(point)) return;
        Drawing.pointsCache.push(point);
        Drawing.endLineAtPoint(point);
    },

    endLineAtPoint: function (point) {
        Drawing.sprite.lineTo(point.x, point.y);
        Drawing.sprite.stroke();
        Drawing.sprite.beginPath();
        Drawing.sprite.moveTo(point.x, point.y);
    },

    drawPoint: function (point) {
        //Drawing.sprite.clearRect(0, 0, Drawing.canvasWidth, Drawing.canvasHeight);
        Drawing.sprite.lineTo(point.x+0.1, point.y+0.1);
        Drawing.sprite.stroke();
    },

    fill: function(point) {
        Drawing.pointsCache.push({x:point.x, y:point.y});

        var canvasRatio = Drawing.canvas.width / Drawing.canvasWidth;
        point.x = Math.round(point.x * canvasRatio);
        point.y = Math.round(point.y * canvasRatio);
        var fillImageData = Drawing.getTempImageData();
        var imageData = Drawing.sprite.getImageData(0, 0, Drawing.canvas.width, Drawing.canvas.height);
        //$('body').append(Drawing.tempCanvas);
        var canvasWidth = Drawing.canvas.width;
        var canvasHeight = Drawing.canvas.height;
        var index = 4 * (point.x + canvasWidth * point.y);
        var startColor = [imageData.data[index],imageData.data[index+1],imageData.data[index+2],imageData.data[index+3]];
        // Don't fill when the color stays the same
        if (startColor[0] === Drawing.color[0] && startColor[1] === Drawing.color[1] && startColor[2] === Drawing.color[2]) {
            return false;
        }
        fillColorAt(index);
        var endPoints = [point];
        var i = 0;
        while (endPoints.length > 0 && i++ < 90000) {
            var newEndPoints = [];
            for (var ep = 0; ep < endPoints.length; ep++) {
                var neighbours = getNewSurroundingPoints(endPoints[ep]);
                for (var n = 0; n < neighbours.length; n++) {
                    var neighbour = neighbours[n];
                    index = 4 * (neighbour.x + canvasWidth * neighbour.y);
                    if (isSameColor(index)) {
                        fillColorAt(index);
                        newEndPoints.push(neighbour);
                    } else {
                        fillColorAt(index, 1);
                    }
                }
            }
            endPoints = newEndPoints;
        }
        Drawing.tempCanvas.getContext('2d').putImageData(fillImageData, 0, 0);
        Drawing.sprite.filter = 'drop-shadow(1px 1px 1px rgb(' + Drawing.color.join(',') + ')) drop-shadow(-1px -1px 1px rgb(' + Drawing.color.join(',') + ')) blur(0)';
        Drawing.sprite.drawImage(Drawing.tempCanvas, 0, 0, 1000 , 1000);
        Drawing.sprite.filter = 'none';

        Drawing.flushPointsCache();

        function getNewSurroundingPoints(point) {
            var points = [];
            if (point.y > 0 && !isVisited(point.x, point.y - 1)) {
                points.push(new Point(point.x, point.y - 1));
            }
            if (point.y < canvasHeight - 1 && !isVisited(point.x, point.y + 1)) {
                points.push(new Point(point.x, point.y + 1));
            }
            if (point.x > 0 && !isVisited(point.x - 1, point.y - 1)) {
                points.push(new Point(point.x - 1, point.y));
            }
            if (point.x < canvasWidth - 1 && !isVisited(point.x + 1, point.y + 1)) {
                points.push(new Point(point.x + 1, point.y));
            }
            return points;
        }

        function isSameColor(index) {
            var tolerance = 30;
            return Math.abs(imageData.data[index] - startColor[0]) < tolerance
                && Math.abs(imageData.data[index + 1] - startColor[1]) < tolerance
                && Math.abs(imageData.data[index + 2] - startColor[2]) < tolerance;
        }

        function fillColorAt(index, alpha) {
            alpha = alpha || 255;
            fillImageData.data[index] = Drawing.color[0];
            fillImageData.data[index + 1] = Drawing.color[1];
            fillImageData.data[index + 2] = Drawing.color[2];
            fillImageData.data[index + 3] = alpha;
        }

        function isVisited(x, y) {
            return fillImageData.data[4 * (x + canvasWidth * y) + 3] > 0;
        }
    },

    getTempImageData: function() {
        var tempSprite = Drawing.tempCanvas.getContext('2d');
        tempSprite.clearRect(0, 0, Drawing.tempCanvas.width, Drawing.tempCanvas.height);
        return tempSprite.getImageData(0, 0, Drawing.tempCanvas.width, Drawing.tempCanvas.height);
    },

    fillTest: function(point) {
        var fillColorR = Drawing.color[0];
        var fillColorG = Drawing.color[1];
        var fillColorB = Drawing.color[2];
        var lll = Drawing.canvas.width / Drawing.canvasWidth;
        startX = Math.round(point.x * lll);
        startY = Math.round(point.y * lll);
        var pixelStack = [[Math.round(startX), Math.round(startY)]];
        var imageData = Drawing.sprite.getImageData(0, 0, Drawing.canvas.width, Drawing.canvas.height);
        console.log(startX, startY, Drawing.canvasRatio, pixelStack[0], imageData.data.length / 4);
        var pixelPos = (startY * Drawing.canvas.width + startX) * 4,
            startR = imageData.data[pixelPos],
            startG = imageData.data[pixelPos + 1],
            startB = imageData.data[pixelPos + 2],
            startA = imageData.data[pixelPos + 3];

        if (fillColorR === startR && fillColorB === startB && fillColorG === startG) {
            // Return because trying to fill with the same color
            return;
        }

        while (pixelStack.length) {
            var newPos, x, y, reachLeft, reachRight;
            newPos = pixelStack.pop();
            x = newPos[0];
            y = newPos[1];

            pixelPos = (y * Drawing.canvas.width + x) * 4;
            while (y-- >= 0 && matchStartColor(pixelPos)) {
                pixelPos -= Drawing.canvas.width * 4;
            }
            colorPixel(pixelPos);
            pixelPos += Drawing.canvas.width * 4;
            ++y;
            reachLeft = false;
            reachRight = false;
            //colorPixel(pixelPos);
            while (y++ < Drawing.canvas.height - 1 && matchStartColor(pixelPos)) {
                colorPixel(pixelPos);
                if (x > 0) {
                    if (matchStartColor(pixelPos - 4)) {
                        if (!reachLeft) {
                            pixelStack.push([x - 1, y]);
                            reachLeft = true;
                        }
                    } else if (reachLeft) {
                        colorPixel(pixelPos - 4);
                        reachLeft = false;
                    } else {
                        colorPixel(pixelPos - 4);
                    }
                }

                if (x < Drawing.canvas.width - 1) {
                    if (matchStartColor(pixelPos + 4)) {
                        if (!reachRight) {
                            pixelStack.push([x + 1, y]);
                            reachRight = true;
                        }
                    } else if (reachRight) {
                        colorPixel(pixelPos + 4);
                        reachRight = false;
                    } else {
                        colorPixel(pixelPos + 4);
                    }
                }

                pixelPos += Drawing.canvas.width * 4;
            }
            colorPixel(pixelPos);
        }

        Drawing.sprite.putImageData(imageData, 0, 0);

        function matchStartColor(pixelPos) {
            var r = imageData.data[pixelPos];
            var g = imageData.data[pixelPos + 1];
            var b = imageData.data[pixelPos + 2];

            return (r == startR && g == startG && b == startB);
        }

        function colorPixel(pixelPos) {
            imageData.data[pixelPos] = fillColorR;
            imageData.data[pixelPos + 1] = fillColorG;
            imageData.data[pixelPos + 2] = fillColorB;
            imageData.data[pixelPos + 3] = 255;
        }
    },

    useNewPoint: function (newPoint) {
        var prevPoint = Drawing.lastPointInCache();
        return (Math.abs(prevPoint.x - newPoint.x) > Drawing.newPointMinDist || Math.abs(prevPoint.y - newPoint.y) > Drawing.newPointMinDist);
    },

    lastPointInCache: function () {
        return Drawing.pointsCache[Drawing.pointsCache.length-1];
    },

    pointFromEvent: function (e) {
        return new Point(Math.round(e.layerX * Drawing.canvasRatio), Math.round(e.layerY * Drawing.canvasRatio));
    },

    clear: function () {
        Drawing.sprite.fillStyle = "white";
        Drawing.sprite.rect(0, 0, Drawing.canvasWidth, Drawing.canvasHeight);
        Drawing.sprite.fill();
        Drawing.pointsCache = [];
        Drawing.imageCache = [];
    },

    draw: function(imageCache) {
        Drawing.imageCache = imageCache;
        for (var i in imageCache) {
            var obj = imageCache[i];
            if (typeof obj.tool != 'undefined') {
                Drawing.setTool(obj.tool);
                Drawing.getTool().init(obj);
            } else {
                switch (Drawing.tool) {
                    case Drawing.TOOL_BRUSH:
                        var startPoint = obj.shift();
                        Drawing.startLineAtPoint(startPoint);
                        if (obj.length) {
                            for (var p in obj) {
                                Drawing.endLineAtPoint(obj[p]);
                            }
                        } else {
                            Drawing.drawPoint(startPoint);
                        }
                        break;
                    case Drawing.TOOL_BUCKET:
                        for (var f = 0; f < obj.length; f++) {
                            Drawing.fill(obj[f]);
                        }
                        break;
                }
            }
        }
    }

};

/** Drawing Tools *************************************************************************************************/

var Tools = {

    /** Paint Brush ***********************************************************************************************/

    brush: {
        init: function(settings) {
            if (settings) {
                if (typeof settings.size != 'undefined') {
                    Drawing.setBrushSize(settings.size);
                }
                if (typeof settings.color != 'undefined') {
                    Drawing.setColor(settings.color);
                }
            }
        },

        start: function(point) {
            if (Drawing.mode === Drawing.MODE_IDLE) {
                Drawing.mode = Drawing.MODE_DRAWING;
                Drawing.startLine(point);
            }
        },

        move: function(point) {
            if (Drawing.mode === Drawing.MODE_DRAWING) {
                Drawing.endLine(point);
            }
        },

        end: function(point, inside) {
            if (Drawing.mode === Drawing.MODE_DRAWING) {
                Drawing.mode = Drawing.MODE_IDLE;
                if (inside && Drawing.pointsCache.length == 1) {
                    Drawing.drawPoint(Drawing.lastPointInCache());
                }
                Drawing.flushPointsCache();
            }
        },

        exit: function(point) {
            if (Drawing.mode === Drawing.MODE_DRAWING) {
                Drawing.pointsCache.push(point);
                Drawing.endLineAtPoint(point);
                Drawing.flushPointsCache();
            }
        },

        enter: function (point) {
            if (Drawing.mode === Drawing.MODE_DRAWING) {
                Drawing.startLine(point);
            }
        },

        toArray: function() {
            return {
                tool: Drawing.TOOL_BRUSH,
                color: Drawing.color,
                size: Drawing.brushSize
            };
        }
    },

    /** Paint Bucket **********************************************************************************************/

    bucket: {
        lastCompleteTime: null,

        init: function(settings) {
            if (settings) {
                if (typeof settings.color != 'undefined') {
                    Drawing.setColor(settings.color);
                }
            }
        },

        end: function(point, inside) {
            var now = Date.now();
            if (inside && (!this.lastCompleteTime || now - this.lastCompleteTime > 100)) {
                Drawing.fill(point);
                this.lastCompleteTime = Date.now();
            } else {
                console.log('nope');
            }
        },

        toArray: function() {
            return {
                tool: Drawing.TOOL_BUCKET,
                color: Drawing.color
            };
        },

        start: function(){},
        move: function(){},
        exit: function(){},
        enter: function(){}
    }

};

function Point(x, y) {
    this.x = x;
    this.y = y;
}
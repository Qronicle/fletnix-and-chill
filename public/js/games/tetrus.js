function empty(){}

function Tetrus() {

    this.STATUS_IDLE = 'idle';
    this.STATUS_ACTIVE = 'active';
    this.STATUS_VIEW = 'view';

    this.field = [];
    this.width = 10;
    this.height = 20;
    this.status = this.STATUS_IDLE;

    this._queue = [];
    this._tick = null;
    this._interval = 500;
    this._moveLeftInterval = null;
    this._moveRightInterval = null;
    this._dropInterval = null;
    this._queuePreview = null;

    this.onBlockPlaced = empty;
    this.onBlockMoved = empty;
    this.onGameOver = empty;

    this.currentTetromino = {

        position: [0, 0],
        rotation: 0,
        shape: "L",
        rotations: [],
        size: 0,

        prevPosition: null,
        prevRotation: null,

        init: function(tetromino) {
            this.position = [0, 0];
            this.rotation = 0;
            this.shape = tetromino.name;
            this.rotations = tetromino.shapes;
            this.size = tetromino.shapes[0].length;
        },
        startTransaction: function() {
            this.prevPosition = this.position.slice(0);
            this.prevRotation = this.rotation;
        },
        rollback: function() {
            this.position = this.prevPosition;
            this.rotation = this.prevRotation;
        },
        toArray: function() {
            return {
                position: this.position,
                rotation: this.rotation,
                shape: this.shape
            }
        }
    };

    this.tetrominos = [
        {
            name: 'I',
            shapes: [
                [
                    [0, 0, 0, 0],
                    [1, 1, 1, 1],
                    [0, 0, 0, 0],
                    [0, 0, 0, 0]
                ], [
                    [0, 0, 1, 0],
                    [0, 0, 1, 0],
                    [0, 0, 1, 0],
                    [0, 0, 1, 0]
                ], [
                    [0, 0, 0, 0],
                    [0, 0, 0, 0],
                    [1, 1, 1, 1],
                    [0, 0, 0, 0]
                ], [
                    [0, 1, 0, 0],
                    [0, 1, 0, 0],
                    [0, 1, 0, 0],
                    [0, 1, 0, 0]
                ]
            ]
        }, {
            name: 'J',
            shapes: [
                [
                    [1, 0, 0],
                    [1, 1, 1],
                    [0, 0, 0]
                ], [
                    [0, 1, 1],
                    [0, 1, 0],
                    [0, 1, 0]
                ], [
                    [0, 0, 0],
                    [1, 1, 1],
                    [0, 0, 1]
                ], [
                    [0, 1, 0],
                    [0, 1, 0],
                    [1, 1, 0]
                ]
            ]
        }, {
            name: 'L',
            shapes: [
                [
                    [0, 0, 1],
                    [1, 1, 1],
                    [0, 0, 0]
                ], [
                    [0, 1, 0],
                    [0, 1, 0],
                    [0, 1, 1]
                ], [
                    [0, 0, 0],
                    [1, 1, 1],
                    [1, 0, 0]
                ], [
                    [1, 1, 0],
                    [0, 1, 0],
                    [0, 1, 0]
                ]
            ]
        }, {
            name: 'O',
            shapes: [
                [
                    [1, 1],
                    [1, 1]
                ],
                [
                    [1, 1],
                    [1, 1]
                ],
                [
                    [1, 1],
                    [1, 1]
                ],
                [
                    [1, 1],
                    [1, 1]
                ]
            ]
        }, {
            name: 'S',
            shapes: [
                [
                    [0, 1, 1],
                    [1, 1, 0],
                    [0, 0, 0]
                ], [
                    [0, 1, 0],
                    [0, 1, 1],
                    [0, 0, 1]
                ], [
                    [0, 0, 0],
                    [0, 1, 1],
                    [1, 1, 0]
                ], [
                    [1, 0, 0],
                    [1, 1, 0],
                    [0, 1, 0]
                ]
            ]
        }, {
            name: 'T',
            shapes: [
                [
                    [0, 1, 0],
                    [1, 1, 1],
                    [0, 0, 0]
                ], [
                    [0, 1, 0],
                    [0, 1, 1],
                    [0, 1, 0]
                ], [
                    [0, 0, 0],
                    [1, 1, 1],
                    [0, 1, 0]
                ], [
                    [0, 1, 0],
                    [1, 1, 0],
                    [0, 1, 0]
                ]
            ]
        }, {
            name: 'Z',
            shapes: [
                [
                    [1, 1, 0],
                    [0, 1, 1],
                    [0, 0, 0]
                ], [
                    [0, 0, 1],
                    [0, 1, 1],
                    [0, 1, 0]
                ], [
                    [0, 0, 0],
                    [1, 1, 0],
                    [0, 1, 1]
                ], [
                    [0, 1, 0],
                    [1, 1, 0],
                    [1, 0, 0]
                ]
            ]
        }
    ];
}

Tetrus.prototype.init = function(containerId) {
    var tetrusContainer = document.getElementById(containerId);
    var fieldHtml = '';
    for (var r = 0; r < this.height; r++) {
        fieldHtml += '<tr>';
        for (var c = 0; c < this.width; c++) {
            fieldHtml += '<td></td>';
        }
        fieldHtml += '</tr>';
    }
    var nextTetrominoHtml = '';
    for (var y = 0; y < 4; y++) {
        nextTetrominoHtml += '<tr>';
        for (var x = 0; x < 4; x++) {
            nextTetrominoHtml += '<td></td>';
        }
        nextTetrominoHtml += '</tr>';
    }
    tetrusContainer.innerHTML =
        '<table class="tetrus tetrus-bg"><tbody>' + fieldHtml + '</tbody></table>' +
        '<table class="tetrus tetrus-fg"><tbody>' + fieldHtml + '</tbody></table>' +
        '<div class="tetrus-next tetrus-next-bg"></div>' +
        '<table class="tetrus-next tetrus-fg"><tbody>' + nextTetrominoHtml + '</tbody></table>';

    var tetrus = tetrusContainer.children[1].children[0]; // The table body
    this._queuePreview = tetrusContainer.children[3].children[0];
    this.field = [];
    for (r = 0; r < this.height; r++) {
        var row = [];
        for (c = 0; c < this.width; c++) {
            row.push({
                val: 0,
                td: tetrus.children[r].children[c]
            });
        }
        this.field.push(row);
    }

    var self = this;
    document.addEventListener('keydown', function(e){self.startInput(e)}, false);
    document.addEventListener('keyup', function(e){self.stopInput(e)});
};

Tetrus.prototype.start = function() {
    this.initNewTetromino();
    this.setStatus(this.STATUS_ACTIVE);
};

Tetrus.prototype.pause = function() {
    this.setStatus(this.STATUS_IDLE);
};

Tetrus.prototype.startView = function() {
    this.initNewTetromino();
    this.setStatus(this.STATUS_VIEW);
};

Tetrus.prototype.clear = function() {
    this.setStatus(this.STATUS_IDLE);
    this._queue = [];
    for (var y = 0; y < this.height; y++) {
        for (var x = 0; x < this.width; x++) {
            this.field[y][x].val = 0;
        }
    }
    this.draw();
};

Tetrus.prototype.setStatus = function(status) {
    if (status === this.status) {
        return;
    }
    switch (status) {
        case this.STATUS_IDLE:
            clearTimeout(this._moveLeftInterval);
            clearInterval(this._moveLeftInterval);
            clearTimeout(this._moveRightInterval);
            clearInterval(this._moveRightInterval);
            clearTimeout(this._dropInterval);
            clearInterval(this._dropInterval);
            clearInterval(this._tick);
            break;
        case this.STATUS_ACTIVE:
            var self = this;
            this._tick = setInterval(function(){self.tick()}, this._interval);
            break;
    }
    this.status = status;
};

Tetrus.prototype.tick = function() {
    this.dropTetromino();
};

Tetrus.prototype.initNewTetromino = function() {
    if (this._queue.length) {
        var index = this._queue.shift();
        this.initTetromino(this.tetrominos[index]);
        this.drawQueuedTetromino();
    } else {
        this.initRandomTetromino();
    }
};

Tetrus.prototype.initRandomTetromino = function() {
    var tetromino = this.tetrominos[Math.floor(Math.random() * this.tetrominos.length)];
    this.initTetromino(tetromino);
};

Tetrus.prototype.queueTetrominos = function(ids) {
    this._queue = ids;
};
Tetrus.prototype.queueTetromino = function(id) {
    this._queue.push(id);
};

Tetrus.prototype.initTetromino = function(tetromino) {
    this.currentTetromino.init(tetromino);
    this.currentTetromino.position[0] = Math.ceil((this.width - this.currentTetromino.size) / 2);
    var added = this.addTetromino();
    if (!added && this.currentTetromino.shape === 'I') {
        this.removeTetromino();
        this.currentTetromino.position[1]--;
        added = this.addTetromino();
    }
    if (!added) {
        this.onGameOver({});
        this.setStatus(this.STATUS_IDLE);
    }
    this.draw();
};

Tetrus.prototype.addTetromino = function(fieldValue) {
    fieldValue = fieldValue || this.currentTetromino.shape;
    var tetromino = this.currentTetromino;
    for (var y = 0; y < tetromino.size; y++) {
        for (var x = 0; x < tetromino.size; x++) {
            if (x + tetromino.position[0] >= 0 && x + tetromino.position[0] < this.width) {
                if (tetromino.rotations[tetromino.rotation][y][x]) {
                    if (y + tetromino.position[1] >= this.height || y + tetromino.position[1] < 0) {
                        return false;
                    } else if (this.field[y + tetromino.position[1]][x + tetromino.position[0]].val === 0) {
                        this.field[y + tetromino.position[1]][x + tetromino.position[0]].val = fieldValue;
                    } else {
                        return false;
                    }
                }
            }
        }
    }
    return true;
};

Tetrus.prototype.removeTetromino = function() {
    var tetromino = this.currentTetromino;
    for (var y = 0; y < tetromino.size; y++) {
        for (var x = 0; x < tetromino.size; x++) {
            if (x + tetromino.position[0] >= 0 && x + tetromino.position[0] < this.width && y + tetromino.position[1] >= 0 && y + tetromino.position[1] < this.height) {
                if (this.field[y + tetromino.position[1]][x + tetromino.position[0]].val === tetromino.shape) {
                    this.field[y + tetromino.position[1]][x + tetromino.position[0]].val = 0;
                }
            }
        }
    }
};

Tetrus.prototype.rotateTetromino = function() {
    this.currentTetromino.startTransaction();
    this.removeTetromino();
    this.currentTetromino.rotation = (this.currentTetromino.rotation + 1) % 4;
    this.wallKickTetromino();
    if (!this.addTetromino()) {
        this.removeTetromino();
        this.currentTetromino.rollback();
        this.addTetromino()
    } else {
        this.tetrominoMoved();
    }
    this.draw();
};

Tetrus.prototype.tetrominoMoved = function() {
    this.onBlockMoved({
        tetromino: this.currentTetromino.toArray()
    });
};

Tetrus.prototype.moveTetromino = function(direction) {
    this.currentTetromino.startTransaction();
    this.removeTetromino();
    this.currentTetromino.position[0] += direction;
    this.wallKickTetromino();
    if (!this.addTetromino()) {
        this.removeTetromino();
        this.currentTetromino.rollback();
        this.addTetromino()
    } else {
        this.tetrominoMoved();
    }
    this.draw();
};

Tetrus.prototype.moveTetrominoLeft = function() {
    this.moveTetromino(-1);
};

Tetrus.prototype.moveTetrominoRight = function() {
    this.moveTetromino(1);
};

Tetrus.prototype.dropTetromino = function() {
    this.currentTetromino.startTransaction();
    this.removeTetromino();
    this.currentTetromino.position[1]++;
    if (!this.addTetromino()) {
        this.removeTetromino();
        this.currentTetromino.rollback();
        this.freezeTetromino();
        return;
    } else {
        this.tetrominoMoved();
    }
    this.draw();
};

Tetrus.prototype.freezeTetromino = function() {
    this.addTetromino(this.currentTetromino.shape + ' frozen');
    var linesRemoved = this.removeLines();
    this.onBlockPlaced({
        tetromino: this.currentTetromino.toArray(),
        numLines: linesRemoved
    });
    this.initNewTetromino();
    this.draw();

};

Tetrus.prototype.wallKickTetromino = function() {
    var tetromino = this.currentTetromino;
    var over = 0;
    if (tetromino.position[0] < 0) {
        var extension = -tetromino.position[0];
        for (var y = 0; y < tetromino.size; y++) {
            for (var x = 0; x < extension; x++) {
                if (tetromino.rotations[tetromino.rotation][y][x] === 1) {
                    over = Math.max(over, extension - x);
                    if (over === extension) {
                        break;
                    }
                }
            }
        }
    } else if (tetromino.position[0] > this.width - tetromino.size) {
        extension = tetromino.position[0] - (this.width - tetromino.size);
        for (y = 0; y < tetromino.size; y++) {
            for (x = tetromino.size - extension; x < tetromino.size; x++) {
                if (tetromino.rotations[tetromino.rotation][y][x] === 1) {
                    over = Math.min(over, -(x - (tetromino.size - extension) + 1));
                    if (over === extension) {
                        break;
                    }
                }
            }
        }
    }
    if (over !== 0) {
        tetromino.position[0] += over;
    }
};

Tetrus.prototype.draw = function() {
    for (y = 0; y < this.height; y++) {
        for (x = 0; x < this.width; x++) {
            this.field[y][x].td.className = 'shape-' + this.field[y][x].val;
        }
    }
};

Tetrus.prototype.removeLines = function() {
    var removedLines = 0;
    for (var y = this.height - 1; y >= 0; y--) {
        var numBlocks = 0;
        for (var x = 0; x < this.width; x++) {
            if (this.field[y][x].val !== 0) {
                numBlocks++;
            }
        }
        if (numBlocks === 0) {
            break;
        } else if (numBlocks === this.width) {
            // Drop other lines
            for (var y2 = y - 1; y2 >= 0; y2--) {
                for (var x2 = 0; x2 < this.width; x2++) {
                    this.field[y2 + 1][x2].val = this.field[y2][x2].val;
                }
            }
            y++;
            removedLines++;
        }
    }
    return removedLines;
};

Tetrus.prototype.addLines = function(lines) {
    var numLines = lines.length;
    if (numLines < 1) return;
    var currentTetrominoOffset = 0;
    for (d = 0; d < numLines; d++) {
        this.currentTetromino.startTransaction();
        this.removeTetromino();
        this.currentTetromino.position[1]++;
        if (!this.addTetromino()) {
            this.removeTetromino();
            this.currentTetromino.rollback();
            this.addTetromino();
            break;
        }
        currentTetrominoOffset++;
    }
    this.currentTetromino.position[1] -= numLines + (numLines - currentTetrominoOffset);
    this.addLinesWithoutValidation(lines);
    this.draw();
    this.onBlockMoved({
        tetromino: this.currentTetromino.toArray(),
        lines: lines
    });
};

Tetrus.prototype.addLinesWithoutValidation = function(lines) {
    // Move all lines up
    for (y = 0; y < this.height - lines.length; y++) {
        for (x = 0; x < this.width; x++) {
            this.field[y][x].val = this.field[y + lines.length][x].val;
        }
    }
    // Add new lines
    for (l = 0, y = this.height - lines.length; l < lines.length; y++, l++) {
        for (x = 0; x < this.width; x++) {
            this.field[y][x].val = lines[l][x];
        }
    }
};

Tetrus.prototype.drawQueuedTetromino = function() {
    var tetromino = this.tetrominos[this._queue[0]];
    for (var y = 0; y < 4; y++) {
        for (var x = 0; x < 4; x++) {
            var c = '';
            if (typeof tetromino.shapes[0][y] !== 'undefined' && typeof tetromino.shapes[0][y][x] !== 'undefined' && tetromino.shapes[0][y][x]) {
                c = 'shape-' + tetromino.name;
            }
            this._queuePreview.children[y].children[x].className = c;
        }
    }
};

Tetrus.prototype.log = function() {
    var table = [];
    for (y = 0; y < this.height; y++) {
        var row = [];
        for (x = 0; x < this.width; x++) {
            row.push(this.field[y][x].val);
        }
        table.push(row);
    }
    console.table(table);
};

Tetrus.prototype.placeBlock = function(tetromino) {
    this.removeTetromino();
    this.currentTetromino.rotation = tetromino.rotation;
    this.currentTetromino.position = tetromino.position;
    this.freezeTetromino();
};

Tetrus.prototype.moveBlock = function(tetromino, lines) {
    this.removeTetromino();
    if (Array.isArray(lines)) {
        this.addLinesWithoutValidation(lines);
    }
    this.currentTetromino.rotation = tetromino.rotation;
    this.currentTetromino.position = tetromino.position;
    this.addTetromino();
    this.draw();
};

Tetrus.prototype.processInput = function(e) {
    switch (e.keyCode) {
        case 37: // LEFT
            this.moveTetromino(-1);
            break;
        case 38: // UP
            this.rotateTetromino();
            break;
        case 39: // RIGHT
            this.moveTetromino(1);
            break;
        case 40: // DOWN
            this.dropTetromino();
            break;
    }
};

Tetrus.prototype.startInput = function(e) {
    if (this.status !== this.STATUS_ACTIVE) {
        return;
    }
    var timeout = 160;
    var interval = 50;
    var self = this;
    switch (e.keyCode) {
        case 37: // LEFT
            if (this._moveLeftInterval !== null) {
                break;
            }
            clearInterval(this._moveRightInterval);
            this._moveRightInterval = null;
            this.moveTetrominoLeft();
            this._moveLeftInterval = setTimeout(function() {
                self._moveLeftInterval = setInterval(function(){
                    self.moveTetrominoLeft();
                }, interval);
            }, timeout);
            break;
        case 38: // UP
            this.rotateTetromino();
            break;
        case 39: // RIGHT
            if (this._moveRightInterval !== null) {
                break;
            }
            clearInterval(this._moveLeftInterval);
            this._moveLeftInterval = null;
            this.moveTetrominoRight();
            this._moveRightInterval = setTimeout(function() {
                self._moveRightInterval = setInterval(function(){
                    self.moveTetrominoRight();
                }, interval);
            }, timeout);
            break;
        case 40: // DOWN
            if (this._dropInterval !== null) {
                break;
            }
            this.dropTetromino();
            this._dropInterval = setTimeout(function() {
                self._dropInterval = setInterval(function(){
                    self.dropTetromino();
                }, interval);
            }, timeout);
            break;
    }
};

Tetrus.prototype.stopInput = function(e) {
    switch (e.keyCode) {
        case 37: // LEFT
            clearInterval(this._moveLeftInterval);
            this._moveLeftInterval = null;
            break;
        case 38: // UP
            break;
        case 39: // RIGHT
            clearInterval(this._moveRightInterval);
            this._moveRightInterval = null;
            break;
        case 40: // DOWN
            clearInterval(this._dropInterval);
            this._dropInterval = null;
            break;
    }
};
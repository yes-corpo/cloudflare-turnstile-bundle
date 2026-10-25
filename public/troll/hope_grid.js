window.CfTurnstileTroll.define('hope_grid', function (troll) {
    'use strict';

    var cells = troll.root.querySelectorAll('[data-cf-troll-cell]');

    Array.prototype.forEach.call(cells, function (cell) {
        troll.on(cell, 'click', function () {
            cell.setAttribute('aria-pressed', 'true' === cell.getAttribute('aria-pressed') ? 'false' : 'true');
        });
    });

    troll.on(troll.root.querySelector('[data-cf-troll-validate]'), 'click', function () {
        var selected = troll.root.querySelectorAll('[data-cf-troll-cell][aria-pressed="true"]').length;
        troll.done(selected ? troll.text('done-' + (1 + Math.floor(Math.random() * 3))) : troll.text('empty'));
    });
});

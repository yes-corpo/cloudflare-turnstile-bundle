window.CfTurnstileTroll.define('stuck_loader', function (troll) {
    'use strict';

    var box = troll.root.querySelector('[data-cf-troll-box]');
    var loader = troll.root.querySelector('[data-cf-troll-loader]');
    var progress = troll.root.querySelector('[data-cf-troll-progress]');
    var bar = troll.root.querySelector('[data-cf-troll-progress-bar]');
    var percent = troll.root.querySelector('[data-cf-troll-percent]');
    var steps = troll.reducedMotion ? [33, 66, 99] : [12, 27, 41, 58, 70, 81, 90, 96, 99];
    var interval = troll.reducedMotion ? 350 : 110;
    var started = false;

    troll.on(box, 'click', function () {
        if (started) {
            return;
        }
        started = true;
        troll.check(box, true);
        loader.hidden = false;

        steps.forEach(function (value, index) {
            troll.later(function () {
                progress.setAttribute('aria-valuenow', String(value));
                bar.style.width = value + '%';
                percent.textContent = value + ' %';
            }, (index + 1) * interval);
        });

        troll.later(function () {
            troll.done(troll.text('done'));
        }, (steps.length + 1) * interval + 2000);
    });
});

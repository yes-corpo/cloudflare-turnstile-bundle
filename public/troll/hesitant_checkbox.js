window.CfTurnstileTroll.define('hesitant_checkbox', function (troll) {
    'use strict';

    var box = troll.root.querySelector('[data-cf-troll-box]');
    var clicks = 0;
    var busy = false;

    troll.on(box, 'click', function () {
        if (busy) {
            return;
        }
        clicks++;
        troll.check(box, true);
        if (1 === clicks) {
            busy = true;
            troll.later(function () {
                troll.check(box, false);
                troll.say(troll.text('suspect'));
                busy = false;
            }, 600);
        } else {
            troll.done(troll.text('done'));
        }
    });
});

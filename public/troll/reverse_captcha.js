window.CfTurnstileTroll.define('reverse_captcha', function (troll) {
    'use strict';

    troll.on(troll.root.querySelector('[data-cf-troll-robot]'), 'click', function () {
        troll.say(troll.text('robot'));
    });
    troll.on(troll.root.querySelector('[data-cf-troll-human]'), 'click', function () {
        troll.done(troll.text('done'));
    });
});

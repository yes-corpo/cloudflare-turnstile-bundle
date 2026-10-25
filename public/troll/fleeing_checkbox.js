window.CfTurnstileTroll.define('fleeing_checkbox', function (troll) {
    'use strict';

    var area = troll.root.querySelector('[data-cf-troll-area]');
    var box = troll.root.querySelector('[data-cf-troll-box]');
    var fleeing = true;
    var chased = false;

    troll.on(area, 'pointermove', function (event) {
        if (!fleeing || 'mouse' !== event.pointerType) {
            return;
        }
        var boxRect = box.getBoundingClientRect();
        var distance = Math.hypot(
            event.clientX - (boxRect.left + boxRect.width / 2),
            event.clientY - (boxRect.top + boxRect.height / 2)
        );
        if (distance > Math.max(boxRect.width, boxRect.height) / 2 + 30) {
            return;
        }
        if (!chased) {
            chased = true;
            troll.tried();
            troll.later(function () { fleeing = false; }, 3000);
        }
        var areaRect = area.getBoundingClientRect();
        box.style.left = Math.round(Math.random() * Math.max(0, areaRect.width - boxRect.width)) + 'px';
        box.style.top = Math.round(Math.random() * Math.max(0, areaRect.height - boxRect.height)) + 'px';
    });

    troll.on(box, 'click', function (event) {
        // Keyboard (detail === 0) and touch are never chased
        if (fleeing && chased && event.detail > 0) {
            return;
        }
        troll.check(box, true);
        troll.done(troll.text('done'));
    });
});

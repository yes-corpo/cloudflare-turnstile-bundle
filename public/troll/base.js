(function () {
    'use strict';

    var STUCK_DELAY = 15000;
    var MESSAGE_DELAY = 1800;
    var COOKIE = 'cf_turnstile_troll';

    var behaviours = {};
    var queued = window.CfTurnstileTroll;
    var api = window.CfTurnstileTroll = {
        push: function (entry) {
            behaviours[entry[0]] = entry[1];
        },
        // Built-in behaviours never replace one pushed by the application, whatever the loading order
        define: function (name, behaviour) {
            if (!Object.prototype.hasOwnProperty.call(behaviours, name)) {
                behaviours[name] = behaviour;
            }
        }
    };

    if (Array.isArray(queued)) {
        queued.forEach(api.push);
    }

    function matches(query) {
        return !!(window.matchMedia && window.matchMedia(query).matches);
    }

    function rememberSeen(cooldown) {
        document.cookie = COOKIE + '=1; Max-Age=' + (parseInt(cooldown, 10) || 0) + '; Path=/; SameSite=Lax'
            + ('https:' === window.location.protocol ? '; Secure' : '');
    }

    function start(host) {
        var root = host.querySelector('[data-cf-troll-root]');
        var realWidget = host.querySelector('.cf-turnstile');
        var behaviour = behaviours[host.getAttribute('data-cf-troll')];
        var reducedMotion = matches('(prefers-reduced-motion: reduce)');

        if (!root || !realWidget || !behaviour || matches('(forced-colors: active)')) {
            return;
        }
        if ('1' === host.getAttribute('data-cf-troll-motion') && reducedMotion) {
            return;
        }

        var texts = root.querySelector('[data-cf-troll-texts]');
        var message = root.querySelector('[data-cf-troll-message]');
        var skip = root.querySelector('[data-cf-troll-skip]');
        var timers = [];
        var finished = false;
        var hasTried = false;

        function end(delay) {
            if (finished) {
                return;
            }
            finished = true;
            timers.forEach(clearTimeout);
            root.setAttribute('aria-busy', 'true');
            window.setTimeout(reveal, delay);
        }

        function reveal() {
            var hadFocus = root.contains(document.activeElement);
            root.remove();
            host.classList.remove('cf-troll-active');
            if (hadFocus) {
                realWidget.setAttribute('tabindex', '-1');
                realWidget.focus();
            }
        }

        function guard(callback) {
            return function () {
                if (finished) {
                    return;
                }
                try {
                    callback.apply(this, arguments);
                } catch (error) {
                    end(0);
                    throw error;
                }
            };
        }

        var troll = {
            root: root,
            reducedMotion: reducedMotion,
            text: function (key) {
                return texts ? texts.getAttribute('data-' + key) || '' : '';
            },
            say: function (text) {
                message.textContent = text;
            },
            on: function (element, type, listener) {
                element.addEventListener(type, guard(listener));
            },
            later: function (callback, delay) {
                timers.push(window.setTimeout(guard(callback), delay));
            },
            check: function (box, checked) {
                box.setAttribute('aria-checked', checked ? 'true' : 'false');
            },
            tried: function () {
                if (hasTried || finished) {
                    return;
                }
                hasTried = true;
                troll.later(function () { skip.hidden = false; }, STUCK_DELAY);
            },
            done: function (text) {
                if (text) {
                    troll.say(text);
                }
                end(text ? MESSAGE_DELAY : 0);
            }
        };

        rememberSeen(host.getAttribute('data-cf-troll-cooldown'));
        host.classList.add('cf-troll-active');
        root.hidden = false;

        skip.addEventListener('click', function () {
            end(0);
        });

        root.addEventListener('click', function (event) {
            if (event.target !== skip) {
                troll.tried();
            }
        });

        guard(behaviour)(troll);
    }

    function startAll() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-cf-troll]'), function (host) {
            try {
                start(host);
            } catch (error) {
                host.classList.remove('cf-troll-active');
                var root = host.querySelector('[data-cf-troll-root]');
                if (root) {
                    root.remove();
                }
                throw error;
            }
        });
    }

    // Deferred scripts run before DOMContentLoaded: wait for it so that every troll script is loaded
    if ('complete' === document.readyState) {
        startAll();
    } else {
        document.addEventListener('DOMContentLoaded', startAll);
    }
})();

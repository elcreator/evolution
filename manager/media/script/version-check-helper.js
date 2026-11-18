(function (root, factory) {
    var exported = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = exported;
    }

    root.evoVersionCheckHelper = exported;

    if (root.document && root.document.addEventListener) {
        root.document.addEventListener('click', function (event) {
            exported.onClick(event, root);
        });
    }
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    /**
     * Asks the Updater plugin for the newest version now, past its once-a-day release cache.
     *
     * The version line of the header menu carries `data-version-check` when the plugin listens to
     * OnManagerPageInit; the plugin answers the POST before the dashboard renders, with JSON
     * `{ok, update, latest, message}`. The answer is shown next to the version, and an available
     * update reloads the dashboard when it is open, so its widget shows the offer.
     *
     * @param {Element} item   the clicked `[data-version-check]` element
     * @param {object}  env    {fetch, document, mainFrame}
     * @return {Promise|null}  null while a check of this item is still running
     */
    function check(item, env) {
        if (item.getAttribute('aria-busy') === 'true') {
            return null;
        }

        var result = item.querySelector('[data-role=result]');
        var meta = env.document.querySelector('meta[name="csrf-token"]');
        var token = meta ? meta.getAttribute('content') : '';

        item.setAttribute('aria-busy', 'true');
        show(result, '…', '');

        return env.fetch('index.php?a=2', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'updater_check=1&_token=' + encodeURIComponent(token || '')
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            var message = data && typeof data.message === 'string' ? data.message : '';

            if (!data || !data.ok) {
                show(result, message || '!', 'is-error');
                return data;
            }

            show(result, message, data.update ? 'is-update' : 'is-current');
            if (data.update) {
                reloadDashboard(env.mainFrame);
            }

            return data;
        }).catch(function () {
            // not JSON: the plugin did not answer (disabled meanwhile, or the session ended)
            show(result, '!', 'is-error');
            return null;
        }).then(function (data) {
            item.removeAttribute('aria-busy');
            return data;
        });
    }

    function show(node, text, state) {
        if (!node) {
            return;
        }
        node.textContent = text ? ' · ' + text : '';
        node.setAttribute('data-state', state);
    }

    function reloadDashboard(frame) {
        try {
            var location = frame && frame.contentWindow ? frame.contentWindow.location : null;
            if (location && /[?&]a=2(?:&|$)/.test(location.search || '')) {
                location.reload();
            }
        } catch (error) {
            // another origin in the frame: leave it
        }
    }

    function onClick(event, root) {
        var target = event.target;
        var item = target && target.closest ? target.closest('[data-version-check]') : null;

        if (!item) {
            return;
        }

        event.preventDefault();
        // keep the menu open so the answer can be read
        if (event.stopPropagation) {
            event.stopPropagation();
        }

        check(item, {
            fetch: root.fetch.bind(root),
            document: root.document,
            mainFrame: root.document.getElementById('mainframe')
        });
    }

    return {
        check: check,
        onClick: onClick
    };
}));

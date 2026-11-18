const test = require('node:test');
const assert = require('node:assert');
const { check } = require('../version-check-helper.js');

function item() {
    const attributes = {};
    const result = { textContent: '', attributes: {}, setAttribute(name, value) { this.attributes[name] = value; } };
    return {
        result,
        getAttribute: (name) => (name in attributes ? attributes[name] : null),
        setAttribute: (name, value) => { attributes[name] = String(value); },
        removeAttribute: (name) => { delete attributes[name]; },
        querySelector: (selector) => (selector === '[data-role=result]' ? result : null),
    };
}

function env(answer, frameSearch = '?a=2') {
    const sent = [];
    let reloads = 0;
    return {
        sent,
        reloads: () => reloads,
        document: { querySelector: (selector) => (selector === 'meta[name="csrf-token"]' ? { getAttribute: () => 'tok en' } : null) },
        mainFrame: { contentWindow: { location: { search: frameSearch, reload() { reloads++; } } } },
        fetch(url, options) {
            sent.push({ url, options });
            if (answer instanceof Error) return Promise.reject(answer);
            return Promise.resolve({ json: () => (typeof answer === 'string' ? Promise.reject(new SyntaxError('html')) : Promise.resolve(answer)) });
        },
    };
}

test('posts the check with the manager CSRF token to the dashboard', async () => {
    const e = env({ ok: true, update: false, message: 'This is the latest version.' });
    await check(item(), e);

    assert.strictEqual(e.sent.length, 1);
    assert.strictEqual(e.sent[0].url, 'index.php?a=2');
    assert.strictEqual(e.sent[0].options.method, 'POST');
    assert.strictEqual(e.sent[0].options.body, 'updater_check=1&_token=tok%20en');
    assert.strictEqual(e.sent[0].options.headers['X-Requested-With'], 'XMLHttpRequest');
});

test('shows the answer next to the version and frees the item', async () => {
    const node = item();
    const e = env({ ok: true, update: false, message: 'This is the latest version.' });
    const running = check(node, e);

    assert.strictEqual(node.getAttribute('aria-busy'), 'true');
    assert.strictEqual(node.result.textContent, ' · …');
    assert.strictEqual(check(node, e), null, 'a second click waits for the first');

    await running;
    assert.strictEqual(node.result.textContent, ' · This is the latest version.');
    assert.strictEqual(node.result.attributes['data-state'], 'is-current');
    assert.strictEqual(node.getAttribute('aria-busy'), null);
    assert.strictEqual(e.reloads(), 0);
});

test('an available update reloads an open dashboard only', async () => {
    const node = item();
    const dashboard = env({ ok: true, update: true, latest: '3.5.x @b796f06', message: 'New version available: 3.5.x @b796f06' });
    await check(node, dashboard);
    assert.strictEqual(node.result.textContent, ' · New version available: 3.5.x @b796f06');
    assert.strictEqual(node.result.attributes['data-state'], 'is-update');
    assert.strictEqual(dashboard.reloads(), 1);

    const editor = env({ ok: true, update: true, message: 'New version available: 3.5.10' }, '?a=27&id=1');
    await check(item(), editor);
    assert.strictEqual(editor.reloads(), 0, 'an open editor is not reloaded');
});

test('a refusal, a page instead of JSON and a network error read as a failure', async () => {
    for (const answer of [{ ok: false, message: 'Could not check for a new version. Try again later.' }, '<html>', new Error('offline')]) {
        const node = item();
        await check(node, env(answer));
        assert.strictEqual(node.result.attributes['data-state'], 'is-error');
        assert.match(node.result.textContent, /^ · (Could not check|!)/);
        assert.strictEqual(node.getAttribute('aria-busy'), null);
    }
});

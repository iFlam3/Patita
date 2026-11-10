/*
 * Patita — JavaScript vanilla con mejora progresiva.
 * Todos los formularios y enlaces funcionan sin JavaScript; este archivo
 * solo agrega: formato de precios con Intl.NumberFormat, catálogo sin
 * recarga (con estados de carga y error), carrito sin salir de la página,
 * selectores de cantidad, protección contra doble envío y pequeños ajustes
 * de accesibilidad.
 */
(function () {
    'use strict';

    var root = document.documentElement;
    root.classList.add('js');

    var locale = root.getAttribute('data-locale') || 'es-US';
    var formatters = {};

    // ------------------------------------------------------------ precios

    function formatPrices(scope) {
        (scope || document).querySelectorAll('data.price[data-currency]').forEach(function (el) {
            var value = Number(el.getAttribute('value'));
            var currency = el.getAttribute('data-currency') || '';
            if (!isFinite(value) || !/^[A-Z]{3}$/.test(currency)) {
                return;
            }
            if (!formatters[currency]) {
                try {
                    formatters[currency] = new Intl.NumberFormat(locale, { style: 'currency', currency: currency });
                } catch (e) {
                    return; // Se conserva el formato del servidor.
                }
            }
            el.textContent = formatters[currency].format(value);
        });
    }

    // ------------------------------------------------------------ utilidades

    function spriteUrl() {
        var use = document.querySelector('svg use');
        var href = use ? (use.getAttribute('href') || '') : '';
        return href.split('#')[0];
    }

    function iconMarkup(name, extraClass) {
        return '<svg class="icon ' + (extraClass || '') + '" aria-hidden="true" focusable="false"><use href="'
            + spriteUrl() + '#i-' + name + '"></use></svg>';
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function debounce(fn, wait) {
        var timer;
        return function () {
            var args = arguments;
            var self = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(self, args); }, wait);
        };
    }

    // ------------------------------------------------------------ avisos (toast)

    var toastRegion = document.querySelector('[data-toast-region]');

    function toast(message, options) {
        if (!toastRegion) {
            return;
        }
        options = options || {};
        var el = document.createElement('div');
        el.className = 'toast' + (options.type === 'error' ? ' toast-error' : '');
        el.innerHTML = iconMarkup(options.type === 'error' ? 'circle-alert' : 'check')
            + '<p>' + escapeHtml(message) + '</p>'
            + (options.linkHref ? '<a href="' + escapeHtml(options.linkHref) + '">' + escapeHtml(options.linkText || 'Ver') + '</a>' : '')
            + '<button type="button" class="btn-icon" aria-label="Cerrar aviso">' + iconMarkup('x', 'icon-sm') + '</button>';
        toastRegion.appendChild(el);
        requestAnimationFrame(function () { el.classList.add('is-visible'); });

        var remove = function () {
            el.classList.remove('is-visible');
            setTimeout(function () { el.remove(); }, 200);
        };
        el.querySelector('button').addEventListener('click', remove);
        var timer = setTimeout(remove, 6000);
        el.addEventListener('mouseenter', function () { clearTimeout(timer); });
        el.addEventListener('focusin', function () { clearTimeout(timer); });
    }

    function updateCartCount(count) {
        document.querySelectorAll('[data-cart-count]').forEach(function (badge) {
            var value = badge.querySelector('[data-cart-count-value]');
            if (value) {
                value.textContent = String(count);
            }
            badge.classList.toggle('is-empty', count === 0);
        });
    }

    // ------------------------------------------------------------ navegación

    document.addEventListener('click', function (event) {
        document.querySelectorAll('details[data-dropdown][open]').forEach(function (details) {
            if (!details.contains(event.target)) {
                details.open = false;
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }
        var open = document.querySelector('details[data-dropdown][open]');
        if (open) {
            open.open = false;
            open.querySelector('summary').focus();
        }
    });

    // ------------------------------------------------------------ cantidad

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-qty-step]');
        if (!button) {
            return;
        }
        var control = button.closest('.qty-control');
        var input = control ? control.querySelector('input[type="number"]') : null;
        if (!input) {
            return;
        }
        var step = Number(button.getAttribute('data-qty-step'));
        var min = Number(input.min || 1);
        var max = Number(input.max || 99);
        var current = parseInt(input.value, 10);
        if (isNaN(current)) {
            current = min;
        }
        var next = Math.min(max, Math.max(min, current + step));
        if (next !== current) {
            input.value = String(next);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // Carrito: actualizar al cambiar la cantidad (con espera para clics seguidos).
    var cartSubmit = debounce(function (form) {
        var input = form.querySelector('input[name="quantity"]');
        if (input && input.checkValidity()) {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    }, 600);

    document.addEventListener('change', function (event) {
        var form = event.target.closest('form[data-cart-update]');
        if (form && event.target.matches('[data-autosubmit]')) {
            cartSubmit(form);
        }
    });

    // ------------------------------------------------------------ agregar al carrito

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-add-to-cart]');
        if (!form || !window.fetch) {
            return;
        }
        event.preventDefault();

        var button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' }
        }).then(function (response) {
            return response.json().catch(function () {
                return { ok: false, message: 'No pudimos agregar el producto. Recarga la página e intenta de nuevo.' };
            });
        }).then(function (data) {
            if (typeof data.count === 'number') {
                updateCartCount(data.count);
            }
            if (data.ok) {
                toast(data.message, {
                    linkHref: (document.querySelector('.header-cart') || {}).href,
                    linkText: 'Ver carrito'
                });
            } else {
                toast(data.message || 'No pudimos agregar el producto.', { type: 'error' });
            }
        }).catch(function () {
            toast('No pudimos conectar con la tienda. Revisa tu conexión e intenta de nuevo.', { type: 'error' });
        }).then(function () {
            if (button) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    });

    // ------------------------------------------------------------ catálogo sin recarga

    var catalogForm = document.querySelector('[data-catalog-form]');
    var results = document.querySelector('[data-catalog-results]');
    var filtersPanel = document.querySelector('[data-filters-panel]');
    var desktop = window.matchMedia('(min-width: 1024px)');

    if (filtersPanel) {
        filtersPanel.open = desktop.matches;
        var onMedia = function (e) { filtersPanel.open = e.matches; };
        if (desktop.addEventListener) {
            desktop.addEventListener('change', onMedia);
        }
    }

    if (catalogForm && results && window.fetch && window.history && window.URL) {
        var controller = null;
        var lastUrl = location.href;

        var formUrl = function () {
            var params = new URLSearchParams();
            new FormData(catalogForm).forEach(function (value, key) {
                if (typeof value === 'string' && value.trim() !== '') {
                    params.append(key, value.trim());
                }
            });
            if (params.get('orden') === 'recientes') {
                params.delete('orden');
            }
            var url = new URL(catalogForm.action, location.href);
            url.search = params.toString();
            return url.toString();
        };

        var syncForm = function (url) {
            var params = new URL(url, location.href).searchParams;
            Array.prototype.forEach.call(catalogForm.elements, function (el) {
                if (!el.name || el.type === 'submit') {
                    return;
                }
                if (el.type === 'checkbox') {
                    el.checked = params.get(el.name) === el.value;
                } else if (el.tagName === 'SELECT') {
                    el.value = params.get(el.name) || (el.options[0] ? el.options[0].value : '');
                } else {
                    el.value = params.get(el.name) || '';
                }
            });
        };

        var showError = function (url, push) {
            results.innerHTML = '<div class="state state-error" role="alert">'
                + iconMarkup('circle-alert', 'icon-xl')
                + '<h2>No pudimos cargar los productos</h2>'
                + '<p>Revisa tu conexión e intenta de nuevo. Tus filtros se conservaron.</p>'
                + '<button type="button" class="btn btn-primary" data-retry>Reintentar</button></div>';
            results.querySelector('[data-retry]').addEventListener('click', function () {
                load(url, push, false);
            });
        };

        var load = function (url, push, scroll) {
            if (controller) {
                controller.abort();
            }
            controller = typeof AbortController === 'function' ? new AbortController() : null;

            var status = document.createElement('div');
            status.className = 'loading-status';
            status.setAttribute('role', 'status');
            status.innerHTML = '<span><span class="spinner" aria-hidden="true"></span>Cargando productos…</span>';
            results.prepend(status);
            results.classList.add('is-loading');
            results.setAttribute('aria-busy', 'true');

            var fetchUrl = new URL(url, location.href);
            fetchUrl.searchParams.set('parcial', '1');

            fetch(fetchUrl.toString(), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fetch' },
                signal: controller ? controller.signal : undefined
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.text();
            }).then(function (html) {
                results.innerHTML = html;
                formatPrices(results);
                if (push && url !== location.href) {
                    history.pushState({ catalog: true }, '', url);
                }
                lastUrl = url;
                syncForm(url);
                if (filtersPanel && !desktop.matches) {
                    filtersPanel.open = false;
                }
                if (scroll) {
                    results.scrollIntoView({ block: 'start' });
                    var count = results.querySelector('[data-results-count]');
                    if (count) {
                        count.setAttribute('tabindex', '-1');
                        count.focus({ preventScroll: true });
                    }
                }
            }).catch(function (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                showError(url, push);
            }).then(function () {
                results.classList.remove('is-loading');
                results.removeAttribute('aria-busy');
                var stale = results.querySelector('.loading-status');
                if (stale) {
                    stale.remove();
                }
            });
        };

        catalogForm.addEventListener('submit', function (event) {
            event.preventDefault();
            load(formUrl(), true, false);
        });

        document.addEventListener('change', function (event) {
            var el = event.target;
            if (el.matches('[data-autosubmit]') && el.form === catalogForm) {
                load(formUrl(), true, false);
            }
        });

        results.addEventListener('click', function (event) {
            var link = event.target.closest('a[data-page-link]');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
                return;
            }
            event.preventDefault();
            load(link.href, true, true);
        });

        window.addEventListener('popstate', function () {
            if (location.href !== lastUrl) {
                load(location.href, false, false);
            }
        });
    }

    // ------------------------------------------------------------ formularios

    // Evita doble envío (doble clic) y muestra el estado de envío.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('form[data-once]') || event.defaultPrevented) {
            return;
        }
        if (form.getAttribute('data-submitting') === '1') {
            event.preventDefault();
            return;
        }
        form.setAttribute('data-submitting', '1');
        form.querySelectorAll('button[type="submit"]').forEach(function (button) {
            button.setAttribute('data-original-text', button.textContent);
            if (button.getAttribute('data-loading-text')) {
                button.textContent = button.getAttribute('data-loading-text');
            }
            setTimeout(function () { button.disabled = true; }, 0);
        });
    });

    // Al volver con el botón "Atrás" (bfcache) se rehabilitan los formularios.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) {
            return;
        }
        document.querySelectorAll('form[data-submitting]').forEach(function (form) {
            form.removeAttribute('data-submitting');
            form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                button.disabled = false;
                if (button.getAttribute('data-original-text')) {
                    button.textContent = button.getAttribute('data-original-text');
                }
            });
        });
    });

    // Confirmación de acciones destructivas.
    document.addEventListener('click', function (event) {
        var button = event.target.closest('button[data-confirm]');
        if (!button) {
            return;
        }
        var when = button.getAttribute('data-confirm-when');
        if (when) {
            var select = button.form ? button.form.querySelector('select[name="status"]') : null;
            if (!select || select.value !== when) {
                return;
            }
        }
        if (!window.confirm(button.getAttribute('data-confirm'))) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    // Mostrar u ocultar contraseña.
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) {
            return;
        }
        var input = document.getElementById(toggle.getAttribute('data-password-toggle'));
        if (!input) {
            return;
        }
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Ocultar' : 'Mostrar';
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
    });

    // ------------------------------------------------------------ chat

    (function initChat() {
        var chat = document.querySelector('[data-chat]');
        if (!chat || !window.fetch) {
            return;
        }
        chat.hidden = false;
        var panel = chat.querySelector('.chat-panel');
        var toggle = chat.querySelector('[data-chat-toggle]');
        var log = chat.querySelector('[data-chat-log]');
        var form = chat.querySelector('[data-chat-form]');
        var input = form.querySelector('input');
        var button = form.querySelector('button');
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var token = tokenMeta ? tokenMeta.content : '';
        var busy = false;
        var linkPattern = /(https?:\/\/[^\s)]+|\/[a-z0-9][a-z0-9\-\/?=&#.]*[a-z0-9\/])/gi;

        function setOpen(open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) { input.focus(); } else { toggle.focus(); }
        }

        // Texto plano; las URL y rutas internas se vuelven enlaces sin usar innerHTML.
        function addMessage(text, who) {
            var p = document.createElement('p');
            p.className = 'chat-msg chat-msg-' + who;
            var last = 0;
            text.replace(linkPattern, function (match, _g, offset) {
                var before = offset === 0 ? ' ' : text.charAt(offset - 1);
                if (match.charAt(0) === '/' && !/\s|\(/.test(before)) {
                    return match;
                }
                p.appendChild(document.createTextNode(text.slice(last, offset)));
                var a = document.createElement('a');
                a.href = match;
                a.textContent = match;
                if (/^https?:/i.test(match) && match.indexOf(location.origin) !== 0) {
                    a.target = '_blank';
                    a.rel = 'noopener';
                }
                p.appendChild(a);
                last = offset + match.length;
                return match;
            });
            p.appendChild(document.createTextNode(text.slice(last)));
            log.appendChild(p);
            log.scrollTop = log.scrollHeight;
            return p;
        }

        function post(body) {
            body.append('_token', token);
            return fetch(chat.getAttribute('data-chat-url'), {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' }
            }).then(function (r) {
                return r.json().catch(function () { return { ok: false, message: 'Respuesta inválida del servidor.' }; });
            });
        }

        toggle.addEventListener('click', function () { setOpen(panel.hidden); });
        chat.querySelector('[data-chat-close]').addEventListener('click', function () { setOpen(false); });
        panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') { setOpen(false); } });

        chat.querySelector('[data-chat-reset]').addEventListener('click', function () {
            var body = new FormData();
            body.append('reset', '1');
            post(body);
            while (log.children.length > 1) { log.removeChild(log.lastChild); }
            input.focus();
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var text = input.value.trim();
            if (!text || busy) { return; }
            busy = true;
            button.disabled = true;
            addMessage(text, 'user');
            input.value = '';
            var wait = addMessage('Escribiendo…', 'bot');
            wait.classList.add('chat-msg-wait');
            var body = new FormData();
            body.append('message', text);
            post(body).then(function (data) {
                log.removeChild(wait);
                addMessage(data.ok ? data.reply : (data.message || 'No pude responder.'), data.ok ? 'bot' : 'error');
            }).catch(function () {
                log.removeChild(wait);
                addMessage('Sin conexión. Revisa tu internet e intenta de nuevo.', 'error');
            }).then(function () {
                busy = false;
                button.disabled = false;
                input.focus();
            });
        });
    }());

    // ------------------------------------------------------------ tasa del dólar (Bs)

    // Consulta la tasa del dólar paralelo y muestra el total en bolívares.
    document.querySelectorAll('[data-bs-rate]').forEach(function (box) {
        var button = box.querySelector('[data-bs-rate-btn]');
        var out = box.querySelector('[data-bs-rate-out]');
        var usd = Number(box.getAttribute('data-usd'));
        box.hidden = false;
        button.addEventListener('click', function () {
            button.disabled = true;
            out.textContent = 'Consultando la tasa…';
            fetch('https://ve.dolarapi.com/v1/dolares/paralelo', { headers: { Accept: 'application/json' } })
                .then(function (res) {
                    if (!res.ok) { throw new Error('HTTP ' + res.status); }
                    return res.json();
                })
                .then(function (data) {
                    var rate = Number(data.promedio || data.venta || data.compra);
                    if (!isFinite(rate) || rate <= 0) { throw new Error('Tasa inválida'); }
                    var bs = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    var dollars = new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'USD' });
                    var date = data.fechaActualizacion ? new Date(data.fechaActualizacion).toLocaleString('es-VE') : '';
                    out.innerHTML = '';
                    var lines = [
                        ['Tasa paralelo', 'Bs ' + bs.format(rate) + ' / $'],
                        ['Total en $', dollars.format(usd)],
                        ['Total en Bs', 'Bs ' + bs.format(usd * rate)]
                    ];
                    var dl = document.createElement('dl');
                    lines.forEach(function (pair) {
                        var row = document.createElement('div');
                        var dt = document.createElement('dt');
                        var dd = document.createElement('dd');
                        dt.textContent = pair[0];
                        dd.textContent = pair[1];
                        row.appendChild(dt);
                        row.appendChild(dd);
                        dl.appendChild(row);
                    });
                    out.appendChild(dl);
                    if (date) {
                        var p = document.createElement('p');
                        p.className = 'field-hint';
                        p.textContent = 'Actualizada: ' + date + ' · Fuente: DolarApi';
                        out.appendChild(p);
                    }
                    button.textContent = 'Actualizar tasa';
                })
                .catch(function () {
                    out.textContent = 'No se pudo obtener la tasa. Intenta de nuevo.';
                })
                .then(function () { button.disabled = false; });
        });
    });

    formatPrices(document);
}());

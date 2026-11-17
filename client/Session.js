class Session {
    /**
     * @param {Request} request - экземпляр Request
     * @param {Object} [options]
     * @param {'local'|'cookie'} [options.mode='local'] - где хранить токен:
     *        'local'  — Bearer-токен, клиент хранит его (storage);
     *        'cookie' — httpOnly-cookie, клиент токен не видит, для изменяющих
     *                   запросов отправляется CSRF-заголовок (double-submit).
     * @param {'local'|'session'} [options.storage='local'] - тип storage для режима 'local'
     * @param {string} [options.storageVar='jwt'] - ключ хранения токена
     * @param {string} [options.loginScript='login.php'] - скрипт авторизации
     * @param {string} [options.csrfVar='csrf_token'] - имя cookie с CSRF-токеном
     * @param {string} [options.csrfHeader='X-CSRF-Token'] - заголовок для CSRF-токена
     * @param {RequestCredentials} [options.credentials] - режим credentials для fetch
     *        (по умолчанию 'include' в cookie-режиме, 'same-origin' иначе)
     */
    constructor(request, options = {}) {
        this.request = request;
        this.data = null;
        this.loginScript = options.loginScript ?? 'login.php';
        this.mode = options.mode ?? 'local';
        this.storage = options.storage ?? 'local';
        this.storageVar = options.storageVar ?? 'jwt';
        this.csrfVar = options.csrfVar ?? 'csrf_token';
        this.csrfHeader = options.csrfHeader ?? 'X-CSRF-Token';
        this.credentials = options.credentials ?? (this.mode === 'cookie' ? 'include' : 'same-origin');

        this._listeners = {
            authorize: [],
            logout: [],
        };
        this._prevEvent = null;
        this._loggingOut = false;

        this.request.addInterceptor((ev, config) => {
            if (config == null) {
                return config;
            }
            const headers = config.headers ?? (config.headers = {});

            if (ev === 'before') {
                // В cookie-режиме токен передаёт браузер, заголовок не нужен
                if (this.mode === 'local' && !config?.skipAuth && this?.data?.token) {
                    headers.Authorization = `Bearer ${this.data.token}`;
                }

                if (config.credentials === undefined) {
                    config.credentials = this.credentials;
                }

                // CSRF double-submit: cookie (не httpOnly) читает JS и шлёт в заголовке
                if (this.mode === 'cookie' && !config?.skipAuth) {
                    const csrf = this._readCookie(this.csrfVar);
                    if (csrf) {
                        headers[this.csrfHeader] = csrf;
                    }
                }
            }

            if ((ev === 'after' || ev === 'error') && config.status == 401) {
                this.logout();
            }

            return config;
        });
    }

    enable() {
        if (!this.data) {
            return false;
        }
        return this.mode === 'cookie' ? true : !!this.data.token;
    }

    async authorizeByLogin(login, password) {
        this.data = null;
        try {
            const res = await this.request.post({
                url: this.loginScript,
                data: { login, password },
                skipAuth: true,
            });
            return this._applyAuth(res);
        } catch (error) {
            console.log(error);
            this._resetToken();
            throw new Error('access denied');
        }
    }

    async authorizeByToken() {
        this.data = null;
        try {
            // В cookie-режиме токен уходит автоматически в cookie, тело пустое
            const token = this.mode === 'local' ? this._loadToken() : null;
            if (this.mode === 'local' && !token) {
                throw new Error('no token');
            }

            const res = await this.request.post({
                url: this.loginScript,
                data: this.mode === 'local' ? { token } : {},
            });
            return this._applyAuth(res);
        } catch (error) {
            this._resetToken();
            throw new Error('access denied');
        }
    }

    logout() {
        if (this._loggingOut) {
            return;
        }
        this._loggingOut = true;

        const wasCookieMode = this.mode === 'cookie';
        this._resetToken();
        this._do('logout');

        // В cookie-режиме httpOnly-cookie может удалить только сервер
        if (wasCookieMode) {
            try {
                this.request
                    .post({ url: this.loginScript, data: { action: 'logout' }, skipAuth: true })
                    .catch(() => {});
            } catch (error) {
                /* ignore */
            }
        }

        this._loggingOut = false;
    }

    on(ev, callback) {
        this._listeners[ev].push(callback);
        return () => {
            this._listeners[ev] = this._listeners[ev].filter((f) => f !== callback);
        };
    }

    _applyAuth(res) {
        const { status, data } = res;
        if (status == 401 || !data || !data.success) {
            throw new Error('access denied');
        }

        this.data = data.payload ?? null;

        if (this.mode === 'local' && data.payload?.token) {
            this._saveToken(data.payload.token);
        }

        this._do('authorize');
        return { ...this.data };
    }

    _do(ev, ...params) {
        if (this._prevEvent !== ev) {
            this._prevEvent = ev;
            this._listeners[ev].map((f) => {
                try {
                    f(...params);
                } catch (error) {
                    console.log(error);
                }
            });
        }
    }

    _resetToken() {
        this.request.token = null;
        this.data = null;
        if (this.mode === 'local') {
            this._clearToken();
        }
    }

    _store() {
        return this.storage === 'session' ? sessionStorage : localStorage;
    }

    _saveToken(token) {
        this._store().setItem(this.storageVar, token);
    }

    _loadToken() {
        return this._store().getItem(this.storageVar);
    }

    _clearToken() {
        this._store().removeItem(this.storageVar);
    }

    _readCookie(name) {
        if (typeof document === 'undefined' || !document.cookie) {
            return '';
        }
        const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const match = document.cookie.match(new RegExp('(?:^|; )' + escaped + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }
}

export { Session };

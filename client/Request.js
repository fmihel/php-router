class Request {
    constructor() {
        this.interceptors = [];
        this.router = './index.php';
    }

    async get({
        url = this.router,
        to = undefined,
        attrs = {},
        headers = {},
        noCache = true,
        ...params
    }) {
        let config = { url, to, attrs, headers, ...params };
        config = await this._callInterceptors('before', config);

        const curl = this.prepareUrl(config.url, {
            ...config.attrs,
            ...(noCache ? { _nc: Date.now().toString() } : {}),
        });

        const options = {
            method: 'GET',
            ...(noCache ? { cache: 'no-store' } : {}),
            headers: {
                // ...this.token ? { 'Authorization': `Bearer ${this.token}` } : {}
                ...(noCache
                    ? {
                          ['Cache-Control']: 'no-cache, no-store, must-revalidate',
                          Pragma: 'no-cache',
                      }
                    : {}),
                ...config.headers,
            },
        };

        try {
            const res = await fetch(curl, options);
            const data = await res.json();
            const out = await this._callInterceptors('after', {
                status: res.status,
                url,
                attrs,
                data,
                ...params,
            });
            return out;
        } catch (error) {
            await this._callInterceptors('error', { error, url, attrs, params });
            throw error;
        }
    }

    async post({ url = this.router, to = undefined, data = {}, noCache = true, ...params }) {
        let config = { url, to, data, headers: {}, ...params };
        config = await this._callInterceptors('before', config);

        let body = { to, data: config.data };
        const options = {
            method: 'POST',
            ...(noCache ? { cache: 'no-store' } : {}),
            headers: {
                // ...this.token ? { 'Authorization': `Bearer ${this.token}` } : {},
                'Content-Type': 'application/json',
                ...(noCache
                    ? {
                          ['Cache-Control']: 'no-cache, no-store, must-revalidate',
                          Pragma: 'no-cache',
                      }
                    : {}),
                ...config.headers,
            },
            body: JSON.stringify(body),
        };

        try {
            const res = await fetch(config.url, options);
            const json = await res.json();

            let out = {
                status: res.status,
                url,
                to,
                sendingData: data,
                data: json,
                ...params,
            };

            out = await this._callInterceptors('after', out);
            return out;
        } catch (error) {
            await this._callInterceptors('error', { error, url, data, params });
            throw error;
        }
    }

    async send({ method = 'POST', ...o }) {
        let res;
        if (method === 'POST') {
            res = this.post(o);
        } else {
            res = this.get(o);
        }
        return res;
    }

    params(o) {
        if (o) {
            if ('router' in o) {
                this.router = o.router;
            }
        }

        return { router: this.router };
    }
    addInterceptor(callback) {
        this.interceptors.push(callback);
        return () => {
            this.interceptors = this.interceptors.filter((f) => f !== callback);
        };
    }

    async _callInterceptors(state, config) {
        let out = { ...config };
        for (const fn of this.interceptors) {
            out = await fn(state, config);
        }
        return out;
    }
    /**
     * Формирует URL с GET-параметрами, перезаписывая существующие.
     * Работает как с абсолютными, так и с относительными ссылками.
     *
     * @param {string} url - Исходный URL (полный или относительный)
     * @param {Object} [attrs={}] - Объект с параметрами
     * @returns {string} Готовый URL
     */
    prepareUrl(url, attrs = {}) {
        // Проверяем, является ли ссылка относительной (не начинается с http://, https:// или //)
        const isRelative = !/^(?:[a-z]+:)?\/\//i.test(url);

        // Для относительных путей подставляем временную базу, чтобы сработал new URL()
        const base = isRelative ? 'http://placeholder.local' : undefined;
        const parsedUrl = new URL(url, base);

        // Перебираем объект и перезаписываем параметры через .set()
        Object.entries(attrs).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                parsedUrl.searchParams.set(key, value);
            }
        });

        // Возвращаем полный URL или отрезаем временный домен для относительного пути
        return isRelative
            ? parsedUrl.pathname + parsedUrl.search + parsedUrl.hash
            : parsedUrl.toString();
    }
}

export { Request };

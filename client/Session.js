class Session {
    constructor(request) {
        this.data = null;
        this.request = request;
        this.loginScript = 'login.php';
        this.storageVar = 'jwt';
        this._listeners = {
            autorize: [],
            logout: [],
        };
        this._prevEvent = null;

        this.request.addInterceptor((state, config) => {

            if (state === 'before') {
                if (!(config?.skipAuth) && this?.data?.token) {
                    config.headers.Authorization = `Bearer ${this.data.token}`;
                }
            }

            if (state === 'after') {
                if (config.status == 401){
                    this.logout();
                }
            }
            if (state === 'error') {
                if (config.status == 401){
                    this.logout();
                }
            }
            return config
        })
    }
    enable() {
        return (this.data && this.data.token);
    }
    async autorizeByLogin(login, password) {
        this.data = null;
        try {
            const res = await this.request.post(
                {
                    url: this.loginScript,
                    data: { login, password },
                    skipAuth: true 
                });
            const { status, data } = res;
            if (status == 401 || !data.success) {
                throw new Error('access denied');
            }

            this.data = data.payload;
            this._saveTokenToStorage(data.payload.token);

            this._do('autorize');

        } catch (error) {
            console.log(error);
            this._resetToken();
            throw new Error("access denied");
        }
    }

    async autorizeByToken() {
        this.data = null;
        this.token = null;
        try {
            let token = this._loadTokenFromStorage();
            if (!token) {
                throw new Error("no token");
            }
            const res = await this.request.post({
                url:this.loginScript, 
                data:{ token },
                skipAuth: true 
            });

            const { status, data } = res;

            if (status == 401 || !data.success) {
                throw new Error('access denied');
            }

            this.data = data.payload;
            this._saveTokenToStorage(data.payload.token);
            this._do('autorize');

        } catch (error) {
            this._resetToken();
            throw new Error("access denied");
        }

    }

    logout() {
        this._resetToken();
        this._do('logout');
    }


    on(ev, callback) {
        this._listeners[ev].push(callback);
        return () => { this._listeners = this._listeners.filter(f => f !== callback); };
    }

    _do(ev, ...params) {
        if (this._prevEvent !== ev) {
            this._prevEvent = ev;
            this._listeners[ev].map(f => {
                try {
                    f(...params);
                } catch (error) {
                    console.log(error)
                }
            })
        }
    }

    _resetToken() {
        this.request.token = null;
        this.data = null;
        this._clearTokenStorage();
    }
    _saveTokenToStorage(token) {
        localStorage.setItem(this.storageVar, token);
    }
    _loadTokenFromStorage() {
        return localStorage.getItem(this.storageVar);
    }
    _clearTokenStorage() {
        localStorage.removeItem(this.storageVar);
    }

}


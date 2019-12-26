
class Router {
    constructor() {
        this.host = window.location.href;
        this.events = {
            before: [],
            after: [],
        };
    }

    /** регистрируем события  */
    on(event, callback) {
        if (Array.isArray(callback)) {
            callback.forEach((cb) => this.callback(event, cb));
        } else if (event in this.events) {
            if (this.events[event].indexOf(callback) === -1) this.events[event].push(callback);
        }
        return this;
    }

    do(event, param) {
        let result = true;
        if (event in this.events) {
            this.events[event].find((callback) => {
                try {
                    const ret = callback(param, event);
                    if ((ret !== undefined) && (ret !== true)) {
                        result = ret;
                        return true;
                    }
                } catch (e) {
                    console.error(e);
                    result = false;
                }
                return true;
            });
        }
        return result;
    }


    send(o) {
        return new Promise((ok, err) => {
            const p = $.extend(false, {
                id: -1,
                data: '',
                url: this.host,
                timeout: 2000,
                method: 'POST',
            }, o);

            const pack = { fmihel_router_data: { id: p.id, data: p.data } };


            const evResultBefore = this.do('before', pack.fmihel_router_data);
            if (evResultBefore !== true) {
                // eslint-disable-next-line prefer-promise-reject-errors
                err(typeof evResultBefore === 'string' ? { res: -3, msg: evResultBefore } : { res: -3, msg: 'before return false', ...evResultBefore });
                return;
            }


            $.ajax({
                url: p.url,
                method: p.method,
                timeout: p.timeout,
                data: pack,
            })
                .done((d) => {
                    let errorMsg = { res: 0, msg: 'system', data: null };
                    try {
                        const data = $.parseJSON(d);
                        /** -------------------------------------------------------- */
                        const evResultAfter = this.do('after', ('pack' in data ? data.pack : data));
                        if (evResultAfter !== true) {
                            errorMsg = typeof evResultAfter === 'string' ? { res: -3, msg: evResultAfter } : { res: -3, msg: 'after return false', ...evResultAfter };
                            err(errorMsg);
                            return;
                        }
                        /** -------------------------------------------------------- */

                        if (('pack' in data) && (typeof (data.pack) === 'object') && ('res' in data.pack)) {
                            // eslint-disable-next-line eqeqeq
                            if (data.pack.res == '1') {
                                ok(data.pack.data);
                            } else {
                                errorMsg.res = data.pack.res;
                                errorMsg.msg = data.pack.msg;
                                errorMsg.data = data.pack.data;

                                err(errorMsg);
                            }
                        } else {
                            ok(data.pack);
                        }
                    } catch (e) {
                        console.error('parsing:', d);
                        errorMsg.res = -2;
                        errorMsg.msg = d;
                        errorMsg.data = e;
                        err(errorMsg);
                    }
                })
                .fail((e) => {
                    const errorMsg = { res: -1, msg: 'system', data: e };
                    err(errorMsg);
                });
        });
    }
}

const privateObject = new Router();

// eslint-disable-next-line import/prefer-default-export
export default function router(send = undefined) {
    if (send) {
        return privateObject.send(send);
    } return privateObject;
}

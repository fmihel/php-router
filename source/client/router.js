
class Router {
    constructor() {
        this.host = '/';
    }

    ajax(o) {
        return new Promise((ok, err) => {
            const p = $.extend(false, {
                id: -1,
                data: '',
                url: this.host,
                timeout: 2000,
                method: 'POST',
            }, o);

            const pack = { id: p.id, data: p.data };

            $.ajax({
                url: p.url,
                method: p.method,
                timeout: p.timeout,
                data: pack,
            })
                .done((d) => {
                    const errorMsg = { res: 0, msg: 'system', data: null };
                    try {
                        const data = $.parseJSON(d);

                        if (('pack' in data) && (typeof (data.pack) === 'object') && ('res' in data.pack)) {
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

export const router = new Router();

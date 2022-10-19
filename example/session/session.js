import router from 'http://work/fmihel/router/php-router-client/source/router.js';

class Session{
    constructor(){
        this.enable = false;
        this.id = undefined;
        this.onRouterBefore = this.onRouterBefore.bind(this);
    }
    onRouterBefore(pack){
        return {
            ...pack,
            session:{id:this.id}
        };

    }
    autorize(pass){
        return router.send({
            to:'session/autorize',
            data:{pass}
        })
        .then(({enable,id})=>{
            this.enable = (enable == 1);
            this.id     = id;
            return this.enable;
        });
    }
    logout(){
        this.enable = false;
        this.id = undefined;

        return router.send({
            to:'session/logout',
        });
    }
}

let session = new Session();
router.on('before',session.onRouterBefore);


export default session;
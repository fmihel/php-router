import router from 'http://work/fmihel/router/php-router-client/source/router.js';

class Session{
    constructor(){
        this.id = 'HHSJKDL';
        this.onRouterBefore = this.onRouterBefore.bind(this);
        this.onRouterAfter = this.onRouterAfter.bind(this);
    }
    onRouterBefore(pack){

        return {
            ...pack,
            session:{id:this.id}
        };

    }
    onRouterAfter(pack){

    }
}

let session = new Session();
router.on('before',session.onRouterBefore);


export default session;
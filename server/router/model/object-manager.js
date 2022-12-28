const ObjectManager = function(){
    const qrCodes = {};

    const remove = function(t){
        if(t in qrCodes){
            delete qrCodes[t];
        }
    }

    const set = function(key, value){
        qrCodes[key] = value;
    }

    const get = function(key){
        return qrCodes[key] ?? null;
    }

    return {
        remove,
        set,
        get,
    }
};

module.exports = ObjectManager;
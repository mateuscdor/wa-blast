const ObjectManager = function(){
    const items = {};
    const itemInfo = {};

    const remove = function(t){
        if(t in items){
            delete items[t];
        }
    }

    const set = function(key, value){
        items[key] = value;
    }

    const setInfo = function(key, value){
        log.warn('Setting info for (' + key + '): ' + JSON.stringify(value));
        itemInfo[key] = value;
    }

    const getInfo = function(key, value){
        return itemInfo[key] ?? {};
    }

    const get = function(key){
        return items[key] ?? null;
    }

    const all = function(){
        return Object.keys(items);
    }
    const allInfo = function(){
        return itemInfo;
    }

    return {
        remove,
        set,
        get,
        all,
        setInfo,
        getInfo,
        allInfo,
    };
};

module.exports = ObjectManager;
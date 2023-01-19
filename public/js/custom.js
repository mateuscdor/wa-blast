// Here goes your custom javascript
const MultiInputCreator = function({inputSelector, createdSelector, hiddenSelector, hiddenCreator}){

    let selections = [];
    let mixedId = '';
    const generate = function(){
        let val = $(inputSelector).val();
        if(!val)
            return;
        $(inputSelector).val('');
        selections.push({
            id: Math.random().toFixed(12).substr(3).toString(),
            text: val,
        });
        update();
    }
    const update = function(){
        $(createdSelector).html('');
        for(let {id, text} of selections){
            $(createdSelector).append(
                $(`<div class="multi_input__selector" data-multi-input-id="${id}">
                        ${text} <span class="cursor-pointer" data-multi-input-close="${id}">&times;</span>
                   </div>`)
            );
            $(`[data-multi-input-close="${id}"]`).click(function(){
                selections.splice(selections.findIndex(s => s.id === id), 1);
                $('[data-multi-input-id]').remove();
                update();
            });
        }


        $(hiddenSelector).val(selections.reduce((p, c, cIndex) => hiddenCreator(p, c.text, cIndex), ''));
    }

    const init = function(items = []){
        $(inputSelector).on('keypress', function(e){
            if(e.keyCode === 1 || e.keyCode === 13){
                e.preventDefault();
                generate();
            }
        });
        mixedId = Math.random().toFixed(12).substr(3).toString();
        $(createdSelector).addClass('d-flex gap-2 flex-wrap-1 flex-wrap mt-2');
        selections = [];
        fill(items);
    }
    const fill = function(items){
        selections = [];
        for(let item of items){
            if(!!item || !!item.text){
                selections.push({
                    id: item?.id ?? item,
                    text: item?.text ?? item,
                });
            }
        }
        update();
    }

    return {
        init,
        fill,
        update,
    };
}

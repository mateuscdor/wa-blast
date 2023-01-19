@push('styles')
    <style>
        .cursor-pointer {
            cursor: pointer;
        }
        .spintax_items {
            display: flex;
            flex-direction: row;
            gap: 4px;
            flex-wrap: wrap;
        }
        .spintax_item {
            background-color: #0a58ca30;
            color: #0a58ca;
            font-size: 12px;
            font-weight: 600;
            border-radius: 100px;
            width: fit-content;
            white-space: nowrap;
            padding: 2px 10px;
            display: block;
            user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            -webkit-user-select: none;
        }
        .spintax_item span {
            cursor: pointer;
        }
        .ml-auto {
            margin-left: auto;
        }
    </style>
@endpush

@push('footer')
    <div class="modal fade" id="modal-spintax">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">
                        Spintax Settings
                    </h5>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-spintax-add" data-bs-toggle="pill" data-bs-target="#spintaxAdd" type="button" role="tab" aria-controls="pills-contact" aria-selected="false">Create New</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-spintax-list" data-bs-toggle="pill" data-bs-target="#spintaxList" type="button" role="tab" aria-controls="pills-home" aria-selected="true">Spintax List</button>
                        </li>
                    </ul>
                    <div class="divider my-2"></div>
                    <div class="pt-2">
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade" id="spintaxList" role="tabpanel" aria-labelledby="pills-spintax-list">
                                <div>
                                    <div id="spintax_list" class="d-flex flex-column gap-2">
                                    </div>
                                </div>
                                <div class="d-flex gap-2 justify-content-end mt-4">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                            <div class="tab-pane fade active show" id="spintaxAdd" role="tabpanel" aria-labelledby="pills-spintax-add">
                                <div>
                                    <div class="d-flex flex-column gap-2 flex-wrap">
                                        <div class="">
                                            <label for="spintax_label" class="form-label">
                                                Spintax Name
                                            </label>
                                            <div class="d-flex gap-2">
                                                <input class="form-control form-control-sm" placeholder="Add an Identifier..." id="spintax_label"/>
                                            </div>
                                        </div>
                                        <div class="">
                                            <label for="spintax_input" class="form-label">
                                                Spintax Text
                                            </label>
                                            <div class="d-flex gap-2">
                                                <input class="form-control form-control-sm" placeholder="Add a Text..." id="spintax_input"/>
                                                <button type="button" id="add_spintax" class="btn btn-primary btn-sm">
                                                    Add
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">
                                                Generated Spintax:
                                            </label>
                                            <div class="form-control" style="min-height: 100px; max-width: 100%; word-break: break-word" id="spintax_generated_value">{!! '{{}}' !!}</div>
                                        </div>
                                        <div class="spintax_items" id="spintax_add_items">
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 justify-content-end mt-4">
                                    <button id="copy_spintax_generated_text" type="button" class="btn btn-sm btn-outline-primary" style="margin-right: auto;" data-bs-toggle="tooltip" data-bs-trigger="click" data-bs-title="Copied">Copy Spintax</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                    <button id="insert_spintax_generated_text" type="button" class="btn btn-sm btn-outline-info">Insert Spintax</button>
                                    <button type="button" id="spintax_button_submit" name="submit" class="btn btn-sm btn-primary">Create</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endpush

@push('scripts')


    <script>
        const copyToClipboard = function(text){
            if(typeof navigator?.clipboard?.writeText === 'function'){
                navigator.clipboard.writeText(text);
            } else if(typeof document.execCommand === 'function'){
                let tempInput = document.createElement("input");
                tempInput.style = "position: absolute; left: 0px; top: 0px; opacity: 0;";
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand("copy");
                document.body.removeChild(tempInput);
            }
        }
        const SpintaxCreator = function(){
            let spintaxTexts = [];
            let data;
            let target = null;
            let targetFunc = function(){};

            try {
                data = JSON.parse(localStorage.getItem('spintax_templates') ?? '[]');
            } catch (e){
                data = [];
            }

            const listeners = {
                create: []
            };
            const on = function(eventName, func){
                if(typeof func === "function"){
                    if(listeners[eventName])
                        listeners[eventName].push(func);
                }
            }
            const setTarget = function(el, func){
                if(!(el instanceof $)){
                    el = $(el);
                }
                target = el;
                targetFunc = func;
                if(target){
                    $('#insert_spintax_generated_text').show();
                    $('[id^="spintax_insert_"]').show();
                    $('#modal-spintax').modal('show');
                }
            }
            const off = function(eventName, func){
                if(typeof func === 'function'){
                    let index = listeners[eventName]?.indexOf(func);
                    if(listeners[eventName] && index >= 0)
                        listeners[eventName].splice(index, 1);
                }
            }
            const getData = function(){
                return data;
            }
            const init = function(){
                const spintaxTemplateGenerator = (text, id)=>{
                    return $(`<div class="spintax_item" id="spintax_item_${id}">${text} <span id="spintax_item_close_${id}">&times;</span></div>`);
                };
                const spintaxUpdate = function(){
                    let textarea = $('#spintax_generated_value');
                    textarea.text('\{\{' + spintaxTexts.map(s => s.text).join('|') + '\}\}');

                    $('#spintax_list').html('');
                    for(let item of data){
                        let {label, id, items} = item;
                        $('#spintax_list').append($(`<div id="spintax_list_item_${id}" class="d-flex gap-2 mt-2 align-items-center">
                            <h6 class="mb-0">${label}</h6>
                            <div class="d-flex ml-auto gap-2">
                                <button id="spintax_insert_${id}" type="button" class="btn btn-sm btn-info">
                                    Insert
                                </button>
                                <button id="spintax_copy_${id}" type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-trigger="click" data-bs-title="Copied!">
                                    Copy
                                </button>
                                <button id="spintax_delete_${id}" type="button" class="btn btn-sm btn-danger">
                                    Remove
                                </button>
                            </div>
                        </div>`));
                        $('#spintax_copy_' + id).click(function(e){
                            $('#spintax_copy_' + id).tooltip('show');
                            let text = '\{\{' + items.map(item => item.text).join('|') + '\}\}';
                            copyToClipboard(text);
                            setTimeout(()=>{
                                $('#spintax_copy_' + id).tooltip('hide');
                            }, 1000);
                        });
                        $('#spintax_delete_' + id).click(function(e){
                            data.splice(data.findIndex(d => d.id === id), 1);
                            localStorage.setItem('spintax_templates', JSON.stringify(data));
                            spintaxUpdate();
                            for(let func of listeners.create){
                                func(data);
                            }
                        });
                        $('#spintax_insert_' + id).click(function(e){
                            if(target){
                                let text = '\{\{' + items.map(item => item.text).join('|') + '\}\}';
                                target.val(target.val() + text);
                                targetFunc?.();
                            }
                        });
                    }
                }
                const spintaxTemplateGenerate = function(){
                    let input = $('#spintax_input');
                    if(input.val()){
                        let id = (spintaxTexts[spintaxTexts.length - 1] ?? {id: 0}).id + 1;
                        let newSpintax = spintaxTemplateGenerator(input.val(), id);
                        spintaxTexts.push({
                            id,
                            text: input.val(),
                        });
                        $('#spintax_add_items').append(newSpintax);
                        $('#spintax_item_close_' + id).click(function(){
                            $('#spintax_item_' + id).remove();
                            spintaxTexts.splice(spintaxTexts.findIndex(s => s.id === id), 1);
                            spintaxUpdate();
                        });
                        spintaxUpdate();
                        input.val('');
                    }
                }
                $('#add_spintax').click(function(){
                    spintaxTemplateGenerate()
                });
                $('#spintax_input').keypress(function(e){
                    if(e.keyCode === 1 || e.keyCode === 13){
                        e.preventDefault();
                        spintaxTemplateGenerate()
                    }
                })
                $('#spintax_button_submit').click(function(e){
                    let label = $('#spintax_label');
                    if(spintaxTexts.length && label.val()){
                        let id = Math.random().toFixed(32).toString().substring(4)
                        let newData = {
                            id: id,
                            label: label.val(),
                            items: spintaxTexts,
                        };
                        data.push(newData);
                        localStorage.setItem('spintax_templates', JSON.stringify(data));
                        spintaxTexts = [];
                        label.val('');
                        spintaxUpdate();
                        $('#spintax_add_items').html('');
                        for(let func of listeners.create){
                            func(data);
                        }
                    }
                });
                $('#copy_spintax_generated_text').click(function(e){
                    $(this).tooltip('hide');
                    let text = '\{\{' + spintaxTexts.map(item => item.text).join('|') + '\}\}';
                    copyToClipboard(text);
                    $(this).tooltip('show');
                    setTimeout(()=>{
                        $(this).tooltip('hide');
                    }, 3000);
                });
                $('#insert_spintax_generated_text').click(function(e){
                    if(target){
                        let text = '\{\{' + spintaxTexts.map(item => item.text).join('|') + '\}\}';
                        target.val(target.val() + text);
                        targetFunc?.();
                        spintaxTexts = [];
                        label.val('');
                        spintaxUpdate();
                    }
                    $('#modal-spintax').modal('hide');
                });
                $('#spintax_base_button').click(function(){
                   target = null;
                   targetFunc = function(){};
                   $('#insert_spintax_generated_text').hide();
                    $('[id^="spintax_insert_"]').hide();
                });
                spintaxUpdate();
            };
            return {
                getData,
                init,
                on,
                off,
                setTarget
            }
        }
    </script>
@endpush
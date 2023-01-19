<div id="template_button">
</div>
@push('scripts')
    <script>

        const TemplateButtonCreator = function(){
            let baseEl = `<div id="template_button_container"></div>
                            <div class="d-flex gap-2 mt-3">
                                <button id="template_button_create" type="button" class="btn btn-primary">
                                    Add Button
                                </button>
                            </div>`;

            let buttons = @isset($initial)@if($initial['message']['buttons']){!! json_encode($initial['message']['buttons']) !!}@else[]@endif @else[]@endisset;


            let isTemplate = {{($initial['message']['message_type'] ?? 'template') === 'template'? 'true': 'false'}};

            let createButton = function(id, btn = {}, returns=false){
                $('#template_button_container').append($(`<div id="template_button_${id}_container">
        <div class="col-sm-12 mt-3 mb-2 d-flex justify-content-between align-items-center">
            <h6 id="template_button_${id}_head">
                Button ${returns? buttons.length + 1: buttons.findIndex(i => i.id === id) + 1}
            </h6>
            <button type="button" class="btn btn-outline-danger" id="template_button_${id}_remove">
                Remove
            </button>
        </div>
        <div class="col-sm-12 rounded-3 border border-4 border-light p-3">
            <div class="row">
                <div class="col-sm-12 mt-2">
                    <label for="template_button_${id}_label" class="form-label">Label</label>
                    <input id="template_button_${id}_label" class="form-control" value="${btn?.label ?? ''}" type="text" placeholder="Button Name"/>
                </div>
                <div id="template_button_type_container_${id}" class="col-sm-12 mt-2">
                    ${(btn?.type || !(btn.label || btn.id))? `<label for="template_button_${id}_types" class="form-label">Button Type</label>
                    <select id="template_button_${id}_types" class="form-control">
                        <option value="url" ${btn?.type === 'url' ? 'selected="selected"': ''}>URL</option>
                        <option value="phone" ${btn?.type === 'phone' ? 'selected="selected"': ''}>Phone Number</option>
                        <option value="text" ${btn?.type === 'text' ? 'selected="selected"': ''}>Reply</option>
                    </select>`: ''}
                </div>
                <div id="template_button_${id}_inner" class="col-sm-12 mt-2">
                ${(btn?.text || !(btn.label || btn.id))? `<label for="template_button_${id}_text" class="form-label">URL</label>
                    <input id="template_button_${id}_text" value="${btn?.text ?? ''}" class="form-control" type="url" placeholder="https://example.com..."/>`: ''}
                </div>
            </div>
        </div>
    </div>`));

                // Add Listeners:
                let prefix = "template_button_" + id;
                let url = `<label for="template_button_${id}_text" class="form-label">URL</label>
                    <input id="template_button_${id}_text" class="form-control" type="url" placeholder="https://example.com..."/>`;
                let text = `<label for="template_button_${id}_text" class="form-label">Reply</label>
                    <textarea id="template_button_${id}_text" class="form-control" placeholder="Input some text..."></textarea>`;
                let phoneNumber = `<label for="template_button_${id}_text" class="form-label">Phone Number</label>
                    <input id="template_button_${id}_text" class="form-control" type="tel" placeholder="628888xxxxxx"/>`;

                let templateButtonTypes = `<label for="template_button_${id}_types" class="form-label">Button Type</label>
                    <select id="template_button_${id}_types" class="form-control">
                        <option value="url" ${btn?.type === 'url' ? 'selected="selected"': ''}>URL</option>
                        <option value="phone" ${btn?.type === 'phone' ? 'selected="selected"': ''}>Phone Number</option>
                        <option value="text" ${btn?.type === 'text' ? 'selected="selected"': ''}>Reply</option>
                    </select>`;

                let types = {
                    url: url,
                    phone: phoneNumber,
                    text,
                }

                if(!isTemplate){
                    $(`#template_button_type_container_${id}`).html('');
                    $(`#template_button_${id}_inner`).html('');
                } else {
                    if(!document.getElementById(`template_button_${id}_types`)){
                        $(`#template_button_type_container_${id}`).html(templateButtonTypes);
                    }
                }

                $(`#${prefix}_types`).change(function(){
                    $(`#${prefix}_inner`).html(types[$(this).val()] ?? '')
                });
                $(`#${prefix}_remove`).click(function(){
                    buttons.splice(buttons.findIndex(b => b.id === id), 1);
                    $('#template_button_create').removeClass('d-none');
                    $(`#${prefix}_container`).remove();
                    updateButtonItems();
                });

                return {
                    id,
                    label: '',
                    ...(btn.label && !(btn.text)? {}: {type: 'url', text: ''}),
                    ...btn,
                }
            }

            const updateButtonItems = function(){
                for(let i in buttons){
                    if(!document.querySelector('#template_button_' + buttons[i].id + '_head')){
                        createButton(buttons[i].id, buttons[i]);
                    }

                    $('#template_button_' + buttons[i].id + '_head').text("Button " + (parseInt(i) + 1));
                }
            }

            const resetButtonItems = function(){
                $(`[id^="template_button_"][id$="_container"]:not([id="template_button_container"])`).remove();
                $('#template_button_create').removeClass('d-none');
            }

            const init = function(isButtonOnly = false){

                if(isButtonOnly === isTemplate){
                    isTemplate = !isButtonOnly;
                    buttons = [];
                    resetButtonItems();
                }
                if(!document.querySelector('#template_button_container')){

                    $('#template_button').append($(baseEl));
                    $('#template_button_create').click(function(){
                        const id = Math.random().toString(16).substr(2, 10);
                        buttons.push(createButton(id, {}, true));

                        if(buttons.length >= 3){
                            $('#template_button_create').addClass('d-none');
                        }
                    });
                    for(let button of buttons){
                        createButton(button.id, button);
                    }
                    if(buttons.length >= 3){
                        $('#template_button_create').addClass('d-none');
                    }

                }


            }

            const destroy = function(){
                buttons = [];
                $('#template_button').html('');
            }

            const fill = function(items){
                buttons = items;
                $('#template_button_container').html('');
                for(let button of buttons){
                    createButton(button.id, button);
                }
                if(buttons.length >= 3){
                    $('#template_button_create').addClass('d-none');
                }
            }

            const getButtons = () => buttons;
            const getValue = function(){
                if(!buttons.length){
                    return null;
                }
                return buttons.map(({id: b}) => {
                    return {
                        id: b,
                        label: $('#template_button_' + b + '_label').val(),
                        type: $('#template_button_' + b + '_types').val(),
                        text: $('#template_button_' + b + '_text').val()
                    }
                })
            }

            return {
                getButtons,
                getValue,
                init,
                fill,
                destroy
            }
        }
        

        
        
    </script>
@endpush
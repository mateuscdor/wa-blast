<div id="template_list">

</div>
@push('scripts')
    <script>

        const TemplateListCreator = function(){

            const baseEl = `<div id="template_list_creator">
                                <div class="col-sm-12 mt-2">
                                    <label for="template_list_title" id="template_list__label" class="form-label">List Title</label>
                                    <input class="form-control" id="template_list_title" placeholder="Add a list title...">
                                </div>
                                <div class="col-sm-12 mt-2">
                                    <label for="template_list_header" id="template_list_header_label" class="form-label">List Header</label>
                                    <input class="form-control" id="template_list_header" placeholder="Add a list header...">
                                </div>
                                <div class="col-sm-12 mt-2">
                                    <label for="template_list_button_title" id="template_list_button_label" class="form-label">Button Label</label>
                                    <input class="form-control" id="template_list_button_title" placeholder="Add a button label...">
                                </div>
                                <div id="template_list_container">
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button id="template_list_create" type="button" class="btn btn-primary">
                                        Add List
                                    </button>
                                </div>
                            </div>`;

            let lists = [];
            const createListItem = function(anotherId = undefined){

                const id = anotherId || Math.random().toString(16).substr(2, 10);
                let item = `
                <div id="template_list_${id}_box">
                    <div class="col-sm-12 mt-3 mb-2 d-flex justify-content-between align-items-center">
                        <h6 id="template_list_${id}_label">
                            List ${lists.length + 1}
                        </h6>
                        <button type="button" class="btn btn-outline-danger" id="template_list_${id}_remove">
                            Remove
                        </button>
                    </div>
                    <div class="rounded-3 border border-4 border-light p-3">
                        <div class="row">
                            <div class="col-sm-12">
                                <label for="template_list_${id}_title" id="template_list_${id}_label" class="form-label">Title</label>
                                <input class="form-control" id="template_list_${id}_title" placeholder="Add a title...">
                            </div>
                            <div class="col-sm-12 mt-2">
                                <label for="template_list_${id}_description" id="template_list_${id}_label_result" class="form-label">Description (optional)</label>
                                <textarea class="form-control" id="template_list_${id}_description" placeholder="Add a description..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
                let el = $(item);
                $('#template_list_container').append(el);

                let prefix = 'template_list_' + id;

                $(`#${prefix}_remove`).click(function(){
                    el.remove();
                    lists.splice(lists.indexOf(id), 1);
                    updateListItems();
                    $('#template_list_create').removeClass('d-none');
                });

                return id;
            }

            const updateListItems = function(){
                for(let i in lists){
                    if(!document.getElementById(`template_list_${lists[i]}_box`)){
                        createListItem(lists[i]);
                    }
                    $('#template_list_' + lists[i] + '_label').text("List " + (parseInt(i) + 1));
                }
            }

            const init = function(){
                if(!document.querySelector('#template_list_creator')){
                    $('#template_list').append($(baseEl));
                    $('#template_list_create').click(function(){
                        if(lists.length < 10){
                            let id = Math.random().toString(16).substr(2, 10);
                            createListItem(id);
                            lists.push(id);
                        }
                        if(lists.length >= 10){
                            $('#template_list_create').addClass('d-none');
                        }
                    });
                }
            }
            const destroy = function(){
                lists = [];
                $('#template_list').html('');
            }
            const getLists = ()=>lists;
            const getValue = function(){
                if(!$('#template_list').html())
                    return null;
                return {
                    title: $('#template_list_title').val(),
                    header: $('#template_list_header').val(),
                    button: $('#template_list_button_title').val(),
                    items: lists.map(id => {
                        return {
                            id,
                            title: $(`#template_list_${id}_title`).val(),
                            description: $(`#template_list_${id}_description`).val(),
                        }
                    })
                }
            }

            const fill = function(newLists){
                if(newLists?.items){
                    lists = newLists.items.map(l => l.id);
                    updateListItems();
                    $('#template_list_title').val(newLists.title);
                    $('#template_list_header').val(newLists.header);
                    $('#template_list_button_title').val(newLists.button);
                    for(let {id, title, description} of newLists.items){
                        $(`#template_list_${id}_description`).val(description);
                        $(`#template_list_${id}_title`).val(title);
                    }
                }
            }

            return {
                init,
                destroy,
                getLists,
                getValue,
                fill,
            }
        }
    </script>
@endpush
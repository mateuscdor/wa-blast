<div>
    <label for="message_type" class="form-label">Message Type</label>
    <select name="message_type" id="message_type" class="js-states form-control" tabindex="-1" required>
        <option value="" selected >Select One</option>
        <option value="text">Text Message</option>
        <option value="image">Image Message</option>
        <option value="button">Button Message</option>
        <option value="template">Template Message</option>
        <option value="list">List Message</option>
    </select>
</div>
<div>
    @include('components.creators.media-creator')
    @include('components.creators.body-creator')
    @include('components.creators.footer-creator')
    @include('components.creators.button-creator')
    @include('components.creators.list-creator')
</div>

@push('scripts')
    <script>

        const buttonCreator = TemplateButtonCreator();
        const mediaCreator = TemplateMediaCreator();
        const listCreator = TemplateListCreator();
        const footerCreator = TemplateFooterCreator();
        const bodyCreator = TemplateBodyCreator();

        const currentData = {!!isset($initial)? json_encode($initial->message): "{}"!!};

        $('#message_type').change(function(){
            let value = $(this).val();
            if(!value){
                buttonCreator.destroy();
                mediaCreator.destroy();
                listCreator.destroy();
                bodyCreator.destroy();
                footerCreator.destroy();
            } else if(value === 'text'){
                buttonCreator.destroy();
                mediaCreator.destroy();
                listCreator.destroy();
                bodyCreator.init();
                footerCreator.destroy();
            } else if(value === 'image'){
                buttonCreator.destroy();
                mediaCreator.init();
                listCreator.destroy();
                bodyCreator.init('Caption');
                footerCreator.destroy();
            } else if(value === 'button'){
                buttonCreator.init();
                mediaCreator.init();
                listCreator.destroy();
                bodyCreator.init();
                footerCreator.init();
            } else if(value === 'template'){
                buttonCreator.init();
                mediaCreator.init();
                listCreator.destroy();
                bodyCreator.init();
                footerCreator.init();
            } else if(value === 'list'){
                buttonCreator.destroy();
                mediaCreator.destroy();
                listCreator.init();
                bodyCreator.init();
                footerCreator.init();
            }

        });
        const getAllValues = function(){
            let buttons = buttonCreator.getValue();
            let image = mediaCreator.getValue();
            let list = listCreator.getValue();
            let message = bodyCreator.getValue();
            let footer = footerCreator.getValue();
            let data = {buttons, image, list, message, footer};
            let values = Object.keys(data).reduce((p, key) => {
                if(data[key]){
                    p[key] = data[key];
                }
                return p;
            }, {});
            values.message_type = $('#message_type').val();
            return values;
        }
        $(document).ready(function(){
            @isset($initial)
                $('#message_type').val(currentData.message_type)
                $('#message_type').trigger('change')
                let buttons = currentData.buttons ?? [];
                let list = currentData.list ?? {};
                let footer = currentData.footer ?? "";
                let body = currentData.message ?? "";
                let image = currentData.image ?? "";
                buttonCreator.fill(buttons);
                listCreator.fill(list);
                footerCreator.fill(footer);
                bodyCreator.fill(body);
                mediaCreator.fill(image);
            @endisset
        });
    </script>
@endpush